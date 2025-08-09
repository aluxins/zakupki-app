<?php

namespace App\Http\Services\EisGov;

use App\Http\Responses\ApiResponse;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ConnectException;

class GovRuParser implements HttpInterface
{
    private Client $client;
    private string $baseUri;
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;

        $stack = HandlerStack::create();
        $retryDecider = function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            Exception $exception = null
        ) {
            if ($retries >= 5) {
                return false;
            }
            if ($exception instanceof ConnectException) {
                return true;
            }
            if ($response && $response->getStatusCode() >= 500) {
                return true;
            }
            return false;
        };

        $retryDelay = function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };

        $stack->push(Middleware::retry($retryDecider, $retryDelay));

        $this->client = new Client([
            //'base_uri' => Config::get('eisgov.gov_ru_parser.param.base_uri'),
            'timeout' => 30,
            'connect_timeout' => 30,
            'handler' => $stack,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 YaBrowser/25.6.0.0 Safari/537.36',
                'Referer' => 'https://zakupki.gov.ru'
            ]
        ]);
        $this->baseUri = '';
    }

    public function get(string $uri, array $options = []): array
    {
        $this->setBaseUri($uri);
        $full_uri = $this->baseUri . $this->prepareQueryUri($uri);
        try {
            $contents = Cache::remember('request' . sha1($full_uri . serialize($options)), now()->addMinutes(10),
                function () use ($uri, $full_uri, $options) {
                    try {
                        $response = $this->client->request('GET', $full_uri, $options);
                        return $response->getBody()->getContents();
                    } catch (GuzzleException $e) {
                        return ['error' => sprintf(
                            'HTTP request failed: %s (URI: %s)',
                            $e->getMessage(),
                            $uri
                        )];
                    }
                });
            return $this->prepareContents($uri, $contents);
        } catch (Throwable $e) {
            return ['error' => sprintf(
                'Unexpected error: %s (URI: %s)',
                $e->getMessage(),
                $uri
            )];
        }
    }

    public function post(string $uri, array $options = []): array
    {
        return [];
    }

    /**
     * Формирование базового uri.
     * @param string $key
     * @return void
     */
    public function setBaseUri(string $key): void
    {
        if ($key == 'purchases') {
            $this->baseUri = Config::get('eisgov.gov_ru_parser.base_uri_purchases');
            return;
        }
        if (preg_match('/^purchases\/([0-9]+)([\/a-z]*)$/', $key)) {
            if (empty($this->params['typeFZ'])) {
                throw new RuntimeException('TypeFZ for GovRuParser service is not configured');
            }
            if(str_contains($this->params['typeFZ'], '44')){
                $this->baseUri = Config::get('eisgov.gov_ru_parser.base_uri_purchase_fz44');
            }
            if(str_contains($this->params['typeFZ'], '223')){
                $this->baseUri = Config::get('eisgov.gov_ru_parser.base_uri_purchase_fz223');
            }
            return;
        }
        // Местонахождение заказчика.
        if (preg_match('/^organization\/[0-9]+$/', $key)) {
            $this->baseUri =
                str_contains($this->params['registry_url'], Config::get('eisgov.gov_ru_parser.server_uri'))
                    ? $this->params['registry_url']
                    : Config::get('eisgov.gov_ru_parser.server_uri') . $this->params['registry_url'];
            return;
        }
        $this->baseUri = $key;
    }

    /**
     * Дополнительное формирование uri.
     * @param string $key
     * @return string|null
     */
    private function
    prepareQueryUri(string $key): string|null
    {
        if (preg_match('/^purchases\/([0-9]+)([\/a-z]*)$/', $key, $matches)) {
            $method = 'documents.html';
            if ($matches[2] ?? '' == '/info') {
                $method = 'common-info.html';
            }
            $uri = parse_url($this->params['url'] ?? '');
            return $method . '?' . ($uri['query'] ?? '');
        }
        return null;
    }

    /**
     * Обработка выходных данных.
     * @param string $key
     * @param string $content
     * @return string[]
     */
    private function prepareContents(string $key, string $content): array
    {
        try {
            // Create new instance for parser.
            $crawler = new Crawler($content, $key);
            //$crawler->addHtmlContent($content, 'UTF-8');

            // Список закупок.
            if ($key === 'purchases') {
                $data = $crawler->filterXPath($this->filterStr('div', 'class', 'search-registry-entry-block box-shadow-search-input'))
                    ->each(function (Crawler $node, $i) {
                        $purchase = [
                            'purchase_number' => $node->filterXPath($this->filterStr('div', 'class', 'registry-entry__header-mid__number'))->text(),
                            'purchase_type' => null,
                            'object_info' => $node->filterXPath($this->filterStr('div', 'class', 'registry-entry__body-value'))->text(),
                            'max_price' => $node->filterXPath($this->filterStr('div', 'class', 'price-block__value'))->text(),
                            'region' => 0,
                            'type_fz' => $node->filterXPath($this->filterStr('div', 'class', 'col-9 p-0 registry-entry__header-top__title text-truncate'))->text(),
                            'published_at' => $node->filterXPath($this->filterStr('div', 'class', 'data-block mt-auto'))
                                ->filterXPath($this->filterStr('div', 'class', 'data-block__value'))->first()->text(),
                            'classifier' => null,
                            'url' => $node->filterXPath($this->filterStr('div', 'class', 'registry-entry__header-mid__number'))
                                ->filter('a')->attr('href'),
                            'registry_url' => $node->filterXPath($this->filterStr('div', 'class', 'registry-entry__body-href'))
                                ->filter('a')->attr('href'),
                        ];
                        return (object)[
                            'purchase_number' => preg_replace("~[^0-9]~i", "", $purchase['purchase_number']),
                            'purchase_type' => $purchase['purchase_type'] ?? (str_starts_with($purchase['type_fz'], '44-ФЗ') ? 'epNotificationEF2020_gov' : 'purchaseNoticeEP_gov'),
                            'object_info' => $purchase['object_info'],
                            'max_price' => preg_replace("~[^0-9,]~i", "", $purchase['max_price']),
                            'region' => $purchase['region'],
                            'type_fz' => explode(' ', $purchase['type_fz'])[0] ?? null,
                            'published_at' => Carbon::createFromFormat('d.m.Y', $purchase['published_at'])->format('Y-m-d H:i:s.u'),
                            'classifier' => $purchase['classifier'],
                            'url' => $purchase['url'],
                            'registry_url' => $purchase['registry_url'],
                        ];
                    });
            }

            // Список файлов.
            if (preg_match('/^purchases\/[0-9]+$/', $key)) {
                //$fz = $crawler->filterXPath($this->filterStr('div', 'class', 'registry-entry__header-top__title'))->text();
                // row pl-3 | blockFilesTabDocs
                if (str_contains($this->params['typeFZ'], '44')) {
                    $search = 'blockFilesTabDocs';
                    $organization_tag = 'span';
                    $organization_val = 'cardMainInfo__content';
                }
                if (str_contains($this->params['typeFZ'], '223')) {
                    $search = 'row pl-3';
                    $organization_tag = 'div';
                    $organization_val = 'registry-entry__body-value';
                }

                $organization = $crawler->filterXPath($this->filterStr($organization_tag ?? '', 'class', $organization_val ?? ''))
                    ->each(function (Crawler $node, $i) {
                        $n = $node->children()->each(function (Crawler $node, $i) {
                            return $node->attr('href');
                        });
                        return $n[0] ?? null;
                    });
                $organization = array_values(array_diff($organization, ['']))[0] ?? '';

                $result = $crawler->filterXPath($this->filterStr('div', 'class', $search ?? ''))
                    ->each(function (Crawler $node, $i) {
                        return $node->filter('a')
                            ->each(function (Crawler $node, $i) {
                                if ((str_contains($this->params['typeFZ'], '44') and $node->getNode(0)->hasAttribute('title'))
                                    or (str_contains($this->params['typeFZ'], '223') and !$node->getNode(0)->hasAttribute('shablon-pattern'))) {
                                    return (object)[
                                        'url' => (str_contains($this->params['typeFZ'], '223') ? Config::get('eisgov.gov_ru_parser.server_uri') : '') . $node->first()->attr('href'),
                                        'fileName' => $node->text(),
                                        'fileSize' => 0,
                                        'docDescription' => $node->text(),
                                    ];
                                }
                                return null;
                            });
                    });
                $result = array_filter($result[0] ?? []);
                $data = array_values($result);
            }

            // Общая информация о закупке
            if (preg_match('/^purchases\/[0-9]+[\/a-z]+$/', $key)) {
                if (str_contains($this->params['typeFZ'], '44')) {
                    $tag = 'section';
                    $search = 'blockInfo__section section';
                }
                if (str_contains($this->params['typeFZ'], '223')) {
                    $tag = 'div';
                    $search = 'col-9 mr-auto';
                }
                $data = $crawler->filterXPath($this->filterStr($tag ?? '', 'class', $search ?? ''))
                    ->each(function (Crawler $node, $i)  {
                          $found = $node->children()->each(function (Crawler $node, $i) {
                            return $node->text();
                        });
                        return ['name' => $found[0] ?? 0, 'value' => $found[1] ?? null];
                    });

            }

            // Местонахождение заказчика.
            if (preg_match('/^organization\/[0-9]+$/', $key)) {
                $data = $crawler->filterXPath($this->filterStr('div', 'class', 'registry-entry__body-value'))
                    ->text();
            }

            return [
                'data' => $data ?? ''
            ] + (!empty($organization) ? ['organization' => $organization] : []);
        } catch (\Exception $e) {
            return ['error' => 'Parser error: ' . $e->getMessage()];
        }

    }

    /**
     * Форирование строки вида <code>//div[@ class="className"]</code>
     * @param string $tagName
     * @param string $attrName
     * @param string $attrValue
     * @return string
     */
    private function filterStr(string $tagName, string $attrName = '', string $attrValue = ''): string
    {
        return "//$tagName" . '[@' . $attrName . "='$attrValue']";
    }
}
