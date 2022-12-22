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

  namespace ClicShopping\OM\Module\Hooks\ClicShoppingAdmin\SEO;

  class SeoReportGrammarFrench
  {
    public function __construct()
    {
    }

    private function getGrammar() :array
    {
      $grammar = ['a' => '',  'à' => '', 'et' => '', 'ou' => '',
        'le' => '', 'la' => '', 'les' => '', 'de' => '',
        'des' => '', 'pour' => '', 'non ' => '', 'oui' => '',
        'est' => '', 'sont' => '', 'son' => '',
        'dans' => '', 'plus' => '', 'connexion' => '',
        'déconnexion' => '', 'maintenant' => '', 'propos' => '', 's\'enregister' => '',
        'fermer' => '', 'alors' => '', 'or' => '', 'avec' => '',
        'ils' => '', 'il' => '', 'elle' => '', 'elles' => '', 'je' =>'', 'tu' =>'', 'vous' =>'', 'nous' => '',
        'que' => '', 'qui' => '', 'quoi' => '', 'haut' => '', '
         bas' => '', 'contacter' => '', 'un' => '', 'une' => '',
        'comme' => '', 'par' => '', '-' => '', '*' => '',
        'email' => '', '€' => '', 'Euros' => '', 'recherche' => ''
      ];

      return $grammar;
    }


    public function execute()
    {
      return $this->getGrammar();
    }
  }