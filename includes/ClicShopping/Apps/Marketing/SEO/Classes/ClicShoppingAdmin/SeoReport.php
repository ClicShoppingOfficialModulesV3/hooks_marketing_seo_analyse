<?php
  /**
   *
   * @copyright 2008 - https://www.clicshopping.org
   * @Brand : ClicShopping(Tm) at Inpi all right Reserved
   * @Licence GPL 2 & MIT
   * @licence MIT - Portion of osCommerce 2.4
   * @Info : https://www.clicshopping.org/forum/trademark/
   *
   */

  namespace ClicShopping\Apps\Marketing\SEO\Classes\ClicShoppingAdmin;

  use ClicShopping\OM\HTTP;
  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;

  use GuzzleHttp\Client as GuzzleClient;

  use ClicShopping\Apps\Marketing\SEO\SEO as SEOApp;

  class SeoReport
  {
    protected $urlSite = '';
    protected $linkUrl = '';
    protected $start = null;
    protected $end = null;
    protected $app;
    protected $css;
    protected $js;

    public function __construct($linkUrl = '', $urlSite = '')
    {
      $this->linkUrl = $linkUrl;
      $this->urlSite = $urlSite;

      if (!Registry::exists('SEO')) {
        Registry::set('SEO', new SEOApp());
      }

      $this->app = Registry::get('SEO');

      $this->app->loadDefinitions('Module/Hooks/ClicShoppingAdmin/seo');
    }

    /**
     * This method need to call from your source class file to generate SEO Report
     */
    public function getSeoReport(): string
    {
      $report = [];

//      $report['dnsReachable'] = $this->isDNSReachable($this->linkUrl);

//      if ($report['dnsReachable'] !== false) {
      $isAlive = $this->isAlive();

      if ($isAlive['STATUS'] == true) {
        $this->start = microtime(true);
        $grabbedHTML = $this->grabHTML($this->linkUrl);

         $this->end = microtime(true);

        $report = array_merge($report, $this->getSiteMeta($grabbedHTML));

        $report['isAlive'] = true;

      } else {
        $report['isAlive'] = false;
      }

      $report['url'] = $this->linkUrl;

      $result_html = $this->getHTMLReport($report);

      if ($report['isAlive'] == true) {
        return $result_html;
      } else {
        return $this->getErrorDnsMessage();
      }
    }

    /**
     * check if a url is online/alive
     * @param string $linkUrl : URL of the website
     * @return array $result : This containt HTTP_CODE and STATUS
     */
    private function isAlive(): array
    {
      $client = new GuzzleClient();
      $res = $client->request('GET', $this->linkUrl);
      $int_return_code = $res->getStatusCode();

      $validCodes = [200, 301, 302, 304];

      if (in_array($int_return_code, $validCodes)) {
        return ['HTTP_CODE' => $int_return_code, 'STATUS' => true];
      } else {
        return ['HTTP_CODE' => $int_return_code, 'STATUS' => false];
      }
    }

    /**
     * This private function is used to check the reachable DNS
     * @param {String} $linkUrl : URL of website
     * @return {Boolean} $status : TRUE/FALSE
     */
    private function isDNSReachable(string $linkUrl): bool
    {
      $dnsReachable = checkdnsrr($this->addScheme($linkUrl));

      return $dnsReachable == false ? false : true;
    }

    /**
     * This private function is used to check for file existance on server
     * @param {String} $filename : filename to be check for existance on server
     * @return {Boolean} $status : TRUE/FALSE
     */
    private function checkForFiles(string $filename): bool
    {

      $client = new GuzzleClient();
      $res = $client->request('GET', 'https://www.' . $this->linkUrl . '/' . $filename);
      $httpCode = $res->getStatusCode();

      if ($httpCode == 200) {
        return true;
      } else {
        return false;
      }
    }

    /**
     * This private function is used to check broken link checking
     * @param {String} $link : Link to be test as broken or not
     * @return {Boolean} $status : TRUE/FALSE
     */
    private function brokenLinkTester(string $link): bool
    {
      $client = new GuzzleClient();
      $res = $client->request('GET', $link);
      $httpCode = $res->getStatusCode();

      if ($httpCode == 200) {
        return true;
      } else {
        return false;
      }
    }

    /**
     * This private function is used to check broken link checking for all anchors from page
     * @param {Array} $anchors : Anchor tags from page
     * @return {Number} $count : Count of broken link
     */
    private function getBrokenLinkCount(array $anchors): int
    {
      $count = 0;
      $blinks = [];

      foreach ($anchors as $a) {
        $blinks[] = $a->getAttribute('href');
      }

      if (!empty($blinks)) {
        foreach ($blinks as $ln) {
          $res = $this->brokenLinkTester($ln);
          if ($res) {
            $count++;
          }
        }
      }

      return $count;
    }

    /**
     * This private function is used to check the alt tags for available images from page
     * @param {Array} $imgs : Images from pages
     * @return {Array} $result : Array of results
     */
    private function getImageAltText($imgs): array
    {
      $totImgs = 0;
      $totAlts = 0;

      foreach ($imgs as $im) {
        $totImgs++;

        if (!empty($im->getAttribute('alt'))) {
          $totAlts++;
        }
      }

      return array('totImgs' => $totImgs, 'totAlts' => $totAlts, 'diff' => ($totImgs - $totAlts));
    }

    /**
     * HTTP GET request with curl.
     * @param string $linkUrl : String, containing the URL to curl.
     * @return string : Returns string, containing the curl result.
     */
    private function grabHTML(string $linkUrl)
    {
      $str = HTTP::getResponse(['url' => $linkUrl]);

      return ($str) ? $str : false;
    }

    /**
     * This private function used to check that google analytics is included in page or not
     * @param {Object} $grabbedHtml : Page HTML object
     * @return {Boolean} $result : TRUE/FALSE
     */
    private function findGoogleAnalytics(string $grabbedHtml): bool
    {
      $pos = strrpos($grabbedHtml, 'GoogleAnalyticsObject');
      return ($pos > 0) ? true : false;
    }

    /**
     * This private function used to add http protocol to the url if not available
     * @param {Strin} $linkUrl : This is website url
     * @param {String} $scheme : Protocol Scheme, default http
     */
    private function addScheme(string $linkUrl, string $scheme = 'https://'): string
    {
      return parse_url($linkUrl, PHP_URL_SCHEME) === null ? $scheme . $linkUrl : $linkUrl;
    }

    /**
     * Grammar keyword
     * @return array
     */
    public function grammar(): array
    {
      $CLICSHOPPING_Template = Registry::get('TemplateAdmin');
      $CLICSHOPPING_Hooks = Registry::get('Hooks');

      $source_folder = CLICSHOPPING::getConfig('dir_root', 'Shop') . 'includes/Module/Hooks/ClicShoppingAdmin/SEO/';

      $files_get_array = $CLICSHOPPING_Template->getSpecificFiles($source_folder, 'SeoReportGrammar*');

      foreach ($files_get_array as $grammar) {
        $array_hooks[] = $CLICSHOPPING_Hooks->call('SEO', $grammar['name']);
      }

      $grammar = [];

      if (is_array($array_hooks)) {
        foreach ($array_hooks as $array) {
          $grammar = array_merge($grammar, $array[0]);
        }
      }
      return $grammar;
    }

    /**
     * This private function used to get meta and language information from HTML
     * @param string $grabbedHTML : This is HTML string
     * @return array $report : This is information grabbed from HTML
     */
    private function getSiteMeta($grabbedHTML)
    {
      $html = new \DOMDocument();

      libxml_use_internal_errors(true);
      $html->loadHTML($grabbedHTML);

      libxml_use_internal_errors(false);
      $xpath = new \DOMXPath($html);

      $report = [];
      $langs = $xpath->query('//html');

      foreach ($langs as $lang) {
        $report['language'] = $lang->getAttribute('lang');
      }

      $metas = $xpath->query('//meta');




      foreach ($metas as $meta) {
        if ($meta->getAttribute('name')) {
          $report[$meta->getAttribute('name')] = $meta->getAttribute('content');
        }
      }

      $favicon = $xpath->query('//link[@rel="icon"]');

      if (!empty($favicon)) {
        foreach ($favicon as $fav) {
          $report[$fav->getAttribute('rel')] = $fav->getAttribute("href");
        }
      }

      $title = $xpath->query('//title');

      foreach ($title as $tit) {
        $report['titleText'] = $tit->textContent;
      }

      $report = array_change_key_case($report, CASE_LOWER);

      $onlyText = $this->stripHtmlTags($grabbedHTML);

      if (!empty($onlyText)) {
        $onlyText = array(trim($onlyText));

        $count = $this->getWordCounts($onlyText);

        $count = array_diff_key($count, $this->grammar());

        arsort($count, SORT_DESC | SORT_NUMERIC);

        $report['wordCount'] = $count;
        $report['wordCountMax'] = array_slice($count, 0, 8, true);
      }

      if (!empty($report['wordCount']) && !empty($report['keywords'])) {
        $report['compareMetaKeywords'] = $this->compareMetaWithContent(array_keys($report['wordCount']), $report['keywords']);
      }

      $h1headings = $xpath->query('//h1');
      $index = 0;

      foreach ($h1headings as $h1h) {
        $report['h1'][$index] = trim(strip_tags($h1h->textContent));
        $index++;
      }

      $h2headings = $xpath->query('//h2');
      $index = 0;

      foreach ($h2headings as $h2h) {
        $report['h2'][$index] = trim(strip_tags($h2h->textContent));
        $index++;
      }

      $h3headings = $xpath->query('//h3');
      $index = 0;

      foreach ($h3headings as $h3h) {
        $report['h3'][$index] = trim(strip_tags($h3h->textContent));
        $index++;
      }

      $report['brokenLinkCount'] = 0;
      $anchors = $xpath->query('//a');

      if (!empty($anchors)) {
        //       $report['brokenLinkCount'] = $this->getBrokenLinkCount($anchors);
      }

      $report['images'] = [];
      $imgs = $xpath->query('//img');

      if (!empty($imgs)) {
        $report['images'] = $this->getImageAltText($imgs);
      }

      $report['googleAnalytics'] = $this->findGoogleAnalytics($grabbedHTML);

      $report['pageLoadTime'] = $this->getPageLoadTime();

      $report['flashTest'] = false;
      $flashExists = $xpath->query('//embed[@type="application/x-shockwave-flash"]');

      if ($flashExists->length !== 0) {
        $report['flashTest'] = true;
      }

      $report['frameTest'] = false;
      $frameExists = $xpath->query('//frameset');

      if ($frameExists->length !== 0) {
        $report['frameTest'] = true;
      }

      $report['css'] = [];
      $cssExists = $xpath->query('//link[@rel="stylesheet"]');
      $report['css'] = array_merge($report['css'], $this->cssFinder($cssExists));
      $this->css = $report['css'];

      $report['js'] = [];
      $jsExists = $xpath->query('//script[contains(@src, ".js")]');
      $this->js = array_merge($report['js'], $this->jsFinder($jsExists));

      return $report;
    }

    /**
     * This private function used to find all JS files
     * @param {Array} $jsExists : JS exist count
     * @return {Array} $push : JS result with js counts
     */
    private function jsFinder($jsExists): array
    {
      $push['jsCount'] = 0;
      $push['jsMinCount'] = 0;
      $push['jsNotMinFiles'] = [];

      if (!empty($jsExists)) {
        foreach ($jsExists as $ce) {
          $push['jsCount']++;

          if ($this->formatCheckLinks($ce->getAttribute('src'))) {
            $push['jsMinCount']++;
          } else {
            array_push($push['jsNotMinFiles'], $ce->getAttribute('src'));
          }
        }
      }
      return $push;
    }

    /**
     * This private function used to find all CSS files
     * @param {Array} $cssExists : CSS exist count
     * @return {Array} $push : CSS result with css counts
     */
    private function cssFinder($cssExists) :array
    {
      $push['cssCount'] = 0;
      $push['cssMinCount'] = 0;
      $push['cssNotMinFiles'] = [];

      if (!empty($cssExists)) {
        foreach ($cssExists as $ce) {
          $push['cssCount']++;

          if ($this->formatCheckLinks($ce->getAttribute('href'))) {
            $push['cssMinCount']++;

          } else {
            array_push($push['cssNotMinFiles'], $ce->getAttribute('href'));
          }
        }
      }

      return $push;
    }

    /**
     * This private function used to check format checking for JS and CSS
     * @param {String} $link : JS or CSS file link
     * @return {Boolean} $result : TRUE/FALSE
     */
    private function formatCheckLinks(string $link): bool
    {
      $cssFile = '';
      if (strpos($cssFile, '?') !== false) {
        $cssFile = substr($link, strrpos($link, '/'), strrpos($link, '?') - strrpos($link, '/'));
      } else {
        $cssFile = substr($link, strrpos($link, '/'));
      }
      if (strpos($cssFile, '.min.') !== false) {
        return true;
      } else {
        return false;
      }
    }

    /**
     * This private function used to strip HTML tags from grabbed string
     * @param {String} $str : HTML string to be stripped
     * @return {String} $str : Stripped string
     */
    private function stripHtmlTags(string $str): string
    {
      $str = preg_replace('/(<|>)\1{2}/is', '', $str);
      $str = preg_replace(
        ['@<head[^>]*?>.*?</head>@siu',
          '@<style[^>]*?>.*?</style>@siu',
          '@<script[^>]*?.*?</script>@siu',
          '@<noscript[^>]*?.*?</noscript>@siu',
        ],
        '',
        $str);

      $str = $this->replaceWhitespace($str);
      $str = html_entity_decode($str);
      $str = strip_tags($str);

      return $str;
    }

    /**
     * This private function used to remove whitespace from string, recursively
     * @param {String} $str : This is input string
     * @return {String} $str : Output string, or recursive call
     */
    private function replaceWhitespace(string $str): string
    {
      $result = $str;
      $array = ['  ', '   ', ' \t', ' \r', ' \n',
        '\t\t', '\t ', '\t\r', '\t\n',
        '\r\r', '\r ', '\r\t', '\r\n',
        '\n\n', '\n ', '\n\t', '\n\r'
      ];

      foreach ($array as $replacement) {
        $result = str_replace($replacement, $replacement[0], $result);
      }

      return $str !== $result ? $this->replaceWhitespace($result) : $result;
    }

    /**
     * This private function use to get word count throughout the webpage
     * @param array $phrases : This is array of strings
     * @return array $count : Array of words with count - number of occurences
     */
    private function getWordCounts($phrases)
    {

      $counts = [];
      foreach ($phrases as $phrase) {
        $words = explode(' ', strtolower($phrase));

        $words = array_diff($words, $this->grammar());

        foreach ($words as $word) {
          if (!empty(trim($word))) {
            $word = preg_replace('#[^a-zA-Z\-]#', '', $word);
            if (isset($counts[$word])) {
              $counts[$word] += 1;
            } else {
              $counts[$word] = 1;
            }
          }
        }
      }

      return $counts;
    }

    /**
     * This private function used to compare keywords with meta
     * @param array $contentArray : This is content array
     * @param string $kewordsString : This is meta keyword string
     * @return array $keywordMatch : Match found
     */
    private function compareMetaWithContent(array $contentArray, string $kewordsString): array
    {
      $kewordsString = strtolower(str_replace(',', ' ', $kewordsString));
      $keywordsArray = explode(' ', $kewordsString);
      $keywordMatch = [];

      foreach ($contentArray as $ca) {
        if (!empty(trim($ca)) && in_array($ca, $keywordsArray)) {
          array_push($keywordMatch, $ca);
        }
      }

      return $keywordMatch;
    }


    /**
     * This private function is used to calculate simple load time of HTML page
     */
    private function getPageLoadTime(): bool
    {
      if (!is_null($this->start) && !is_null($this->end)) {
        return $this->end - $this->start;
      } else {
        return 0;
      }
    }

    /**
     * This private function used to clean the string with some set of rules
     * @param {String} $string : String to be clean
     * @return {String} $string : clean string
     */
    private function clean($string) :string
    {
      $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
      $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

      $string = preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.

      return str_replace('-', ' ', $string);
    }

    /**
     * Create HTML to print on PDF (or on page if you want to print on HTML page)
     * Make Sure that HTML is correct, otherwise it will not print on PDF
     * @param {Array} $result : Array having total seo analysis
     * @return {String} $html : Real html which is to be print
     */
    public function getHTMLReport(array $result): string
    {
      if ($result['isAlive'] === true) {
        $content = '<div>';
        $content .= '<table  class="table table-sm table-hover table-striped">';
        $content .= '<thead>';
        $content .= '</thead>';
        $content .= '<tbody>';

        $content .= '<tr>';
        $content .= '<td style="width: 350px;">';
        $content .= '<strong>' . $this->app->getDef('text_url_status') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';
        $content .= $this->app->getDef('text_url_valid_status');
        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_url_google_result_preview') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';
        $content .= $this->app->getDef('text_url_google_result_preview_info') . '<br />';

        if (isset($result['titletext'])) {
          $content .= ' <span  class="text-primary"><u>' . $result['titletext'] . '</u></span><br />';
        }

        $content .= ' <span  class="text-success">' . $this->addScheme($this->linkUrl, 'https://') . '</span><br />';

        if (isset($result['description'])) {
          $content .= ' <span class="text-muted">' . $result['description'] . '</span>';
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_title_tag') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (isset($result['titletext'])) {
          $content .= ' ' . $this->app->getDef('text_title_meta_tag', ['title_text' => strlen($result['titletext'])]) . '<br /> -> <strong>' . $result['titletext'] . '</strong>';
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_error');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_meta_description') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (isset($result['description'])) {
          $content .= ' ' . $this->app->getDef('text_title_meta_tag_description', ['title_description' => strlen($result['description'])]) . ' <br /> -> <strong>' . $result['description'] . '</strong>';
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_error');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_common_keywords') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (!empty($result['wordCountMax'])) {
          $content .= $this->app->getDef('text_common_keywords_info');

          foreach ($result['wordCountMax'] as $wordMaxKey => $wordMaxValue) {
            $content .= '<br />-> <span class="col-md-12"><strong>' . $wordMaxKey . ' - ' . $wordMaxValue . '</strong></span>';
          }
        } else {
          $content .= ' ' . $this->app->getDef('text_common_keywords_info');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_keywords_usage') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (!empty($result['compareMetaKeywords'])) {
          $content .= $this->app->getDef('text_keywords_usage_meta');

          foreach ($result['compareMetaKeywords'] as $metaKey => $metaValue) {
            $content .= '<br /><strong>-> ' . $metaValue . '</strong>';
          }
        } else {
          $content .= ' ' . $this->app->getDef('text_keywords_info');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_heading_status_h1') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (isset($result['h1'])) {
          $content .= $this->app->getDef('text_heading_status_h1_ok');

          foreach ($result['h1'] as $h1) {
            $content .= '<br /><strong>->' . $h1 . '</strong>';
          }
        } else {
          $content .= '<i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_error');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_heading_status_h2') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (isset($result['h2'])) {
          $content .= $this->app->getDef('text_heading_status_h2_ok');

          foreach ($result['h2'] as $h2) {
            $content .= '<br /><strong>->' . $h2 . '</strong>';
          }
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_error');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_heading_status_h3') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (isset($result['h3'])) {
          $content .= $this->app->getDef('text_heading_status_h3_ok');
          foreach ($result['h3'] as $h3) {
            $content .= '<br /><strong>->' . $h3 . '</strong>';
          }
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_error');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_sitemap_test') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';
        $content .= $this->app->getDef('text_sitemap_ok') . ' <span style="color:blue">' . $this->urlSite . 'index.php?Sitemap&GoogleSitemapProducts</span>';
        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_broken_links') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (!empty($result['brokenLinkCount']) && $result['brokenLinkCount'] != 0) {
          $content .= ' <i class="fas fa-times fa-lg" aria-hidden="true"></i>' . $this->app->getDef('text_link_broken_count') . ' ' . $result['brokenLinkCount'];
        } else {
          $content .= ' ' . $this->app->getDef('text_link_broken');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_image_alt') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (!empty($result['images'])) {
          if (isset($result['images']['totImgs']) && $result['images']['totImgs'] != 0) {
            if ($result['images']['diff'] <= 0) {
              $content .= ' ' . $this->app->getDef('text_image_alt_success', ['result_image' => $result['images']['totImgs']]);
            } else {
              $content .= ' <i class="fas fa-times fa-lg text-warning" aria-hidden="true"></i> ' . $this->app->getDef('text_image_not_found', ['result_image' => $result['images']['totImgs'], 'result_image_not_found' => $result['images']['diff']]);
            }
          } else {
            $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_image_error');
          }
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_image_error');
        }

        $content .= '</td>';
        $content .= '</tr>';


        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_google_analytics') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if ($result['googleAnalytics'] === true) {
          $content .= ' ' . $this->app->getDef('text_google_analytics_success');
        } else {
          $content .= ' <i class="fas fa-times fa-lg" aria-hidden="true"></i> ' . $this->app->getDef('text_google_analytics_error');
        }

        $content .= '</td>';
        $content .= '</tr>';


        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_favicon') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (isset($result['shortcut icon']) || isset($result['icon'])) {
          $content .= $this->app->getDef('text_favicon_success');
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_error');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_site_loading') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if ($result['pageLoadTime'] !== 0) {
          $content .= ' ' . $this->app->getDef('text_site_loading_speed', ['result_loading' => $result['pageLoadTime']]);
        } else {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i>' . $this->app->getDef('text_site_loading_error');
        }
        $content .= '</td>';
        $content .= '</tr>';


        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_site_flash') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if ($result['flashTest'] === true) {
          $content .= ' <i class="fas fa-times fa-lg" aria-hidden="true"></i> ' . $this->app->getDef('text_site_flash_success');
        } else {
          $content .= ' ' . $this->app->getDef('text_site_flash_success');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_site_frame') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if ($result['frameTest'] === true) {
          $content .= ' <i class="fas fa-times fa-lg text-danger" aria-hidden="true"></i> ' . $this->app->getDef('text_site_frame_error');
        } else {
          $content .= ' ' . $this->app->getDef('text_site_frame_success');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_site_css_minification') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';


        if (!empty($this->css)) {
          if ($this->css['cssCount'] > 0) {
            $content .= $this->app->getDef('text_site_css_minification_success', ['report_css' => $this->css['cssCount']]);

            if ($this->css['cssMinCount'] > 0) {
              $content .= ' ' . $this->app->getDef('text_site_css_minification_no_success', ['report_no_success_css' => $this->css['cssMinCount']]);
            } else {
              $content .= ' <i class="fas fa-times fa-lg text-danger"" aria-hidden="true"></i>' . $this->app->getDef('text_site_css_minification_error');
            }

            if (!empty($this->css['cssNotMinFiles'])) {
              $content .= ' ' . $this->app->getDef('text_site_css_minification_no_minified');

              foreach ($this->css['cssNotMinFiles'] as $cNMF) {
                $content .= ' <p class="text-info">' . $cNMF . '</p>';
              }
            }
          } else {
            $content .= ' ' . $this->app->getDef('text_site_css_minification_no_external_css_found');
          }
        } else {
          $content .= '<i class="fas fa-times fa-lg text-warning" aria-hidden="true"></i> ' . $this->app->getDef('text_site_css_minification_no_css_found');
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td style="width: 30%;">';
        $content .= '<strong>' . $this->app->getDef('text_site_js_minification') . '</strong>';
        $content .= '</td>';
        $content .= '<td style="width: 70%;">';

        if (!empty($this->js)) {
          if ($this->js['jsCount'] > 0) {
            $content .= $this->app->getDef('text_site_js_minification_external', ['report_js_external' => $this->js['jsCount']]);

            if ($this->js['jsMinCount'] > 0) {
              $content .= ' ' . $this->app->getDef('text_site_js_minification_minified', ['report_js_minified' => $this->js['jsMinCount']]);
            } else {
              $content .= ' ' . $this->app->getDef('text_site_js_minification_no_file_minified');
            }

            if (!empty($this->js['jsNotMinFiles'])) {
              $content .= ' ' . $this->app->getDef('text_site_js_minification_following_minified');

              foreach ($this->js['jsNotMinFiles'] as $jNMF) {
                $content .= ' <p class="text-info">' . $jNMF . '</p>';
              }
            }
          } else {
            $content .= ' No external js found.';
          }
        } else {
          $content .= ' No external js found.';
        }

        $content .= '</td>';
        $content .= '</tr>';

        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</div>';
      }

      $result = $content;

      return $result;
    }

    private function getErrorDnsMessage(): string
    {
      $error = '<div class="separator"></div>';
      $error .= '<div class="alert alert-warning">';
      $error .= '<div>' . $this->app->getDef('text_url_dns_not_found') . '</div>';
      $error .= '</div>';

      return $error;
    }
  }