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

  class SeoReportGrammarEnglish
  {
    public function __construct()
    {
    }

    private function getGrammar() :array
    {
      $grammar = ['a' => '', 'an' => '', 'the' => '', 'shall' => '',
        'should' => '', 'can' => '', 'could' => '', 'will' => '', 'would' => '',
        'am' => '', 'is' => '', 'are' => '', 'been' => '',
        'us' => '', 'has' => '', 'have' => '', 'had' => '',
        'not' => '', 'yes' => '', 'no' => '', 'true' => '',
        'false' => '', 'with' => '', 'to' => '', 'your' => '',
        'more' => '', 'and' => '', 'in' => '', 'out' => '',
        'login' => '', 'logout' => '', 'sign' => '', 'signin' => '',
        'up' => '', 'coming' => '', 'going' => '', 'now' => '',
        'then' => '', 'about' => '', 'for' => '', 'contact' => '',
        'my' => '', 'you' => '', 'go' => '', 'close' => '',
        'of' => '', 'our' => '', 'when' => '', 'where' => '',
        'who' => '', 'account' => '', 'password' => '', 'email' => '',
        'their' => '', '' => '', 'search' => '', 'eur' => '', 'create' => '',
        'hr' => '', 'copyright' => '', 'we' => '',
      ];

      return $grammar;
    }


    public function execute()
    {
      return $this->getGrammar();
    }
  }