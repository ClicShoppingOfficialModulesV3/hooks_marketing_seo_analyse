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

  namespace ClicShopping\Apps\Marketing\SEO\Module\Hooks\ClicShoppingAdmin\Blog;

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\HTTP;

  use ClicShopping\Apps\Marketing\SEO\Classes\ClicShoppingAdmin\SeoReport;

  use ClicShopping\Apps\Marketing\SEO\SEO as SEOApp;

  class PageTabBlogContent implements \ClicShopping\OM\Modules\HooksInterface
  {
    protected $app;
    protected $lang;
    protected $db;
    protected $template;

    public function __construct()
    {
      if (!Registry::exists('SEO')) {
        Registry::set('SEO', new SEOApp());
      }

      $this->app = Registry::get('SEO');
      $this->lang = Registry::get('Language');
      $this->db = Registry::get('Db');

      $this->template = Registry::get('TemplateAdmin');
    }

    public function display()
    {
      if (!defined('CLICSHOPPING_APP_SEO_SE_STATUS') || CLICSHOPPING_APP_SEO_SE_STATUS == 'False') {
        return false;
      }

      $this->app->loadDefinitions('Module/Hooks/ClicShoppingAdmin/Categories/page_tab_content');

      if (isset($_GET['BlogContentEdit'])) {

        $link_url = HTTP::getShopUrlDomain() . 'index.php?&Blog&Content&blog_content_id=' . (int)$_GET['bID'];
        $url_site = HTTP::getShopUrlDomain();

        $this->Report = new SeoReport($link_url, $url_site);

        $report = $this->Report->getSeoReport();

        $content = '<!-- SEO Page report -->';

        if (isset($report)) {
          $content .= $report;

          $tab_title = $this->app->getDef('tab_seo_report');

          $output = <<<EOD
<!-- ######################## -->
<!-- Start Report SEO APP     -->
<!-- ######################## -->
<div class="tab-pane" id="section_SEOReportApp_content">
  <div class="mainTitle">
    <span class="col-md-12">{$tab_title}</span>
  </div>
  <div class="separator"></div>
  {$content}
</div>

<script>
$('#section_SEOReportApp_content').appendTo('#blogContentTabs .tab-content');
$('#blogContentTabs .nav-tabs').append('    <li class="nav-item"><a data-target="#section_SEOReportApp_content" role="tab" data-toggle="tab" class="nav-link">{$tab_title}</a></li>');
</script>
<!-- ######################## -->
<!--  End Report SEO APP      -->
<!-- ######################## -->
EOD;

          return $output;
        }
      }
    }
  }
