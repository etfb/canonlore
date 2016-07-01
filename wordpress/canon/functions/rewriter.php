<?php # rewriter.php

class Rewriter
{
    private $options;
    private $db;
    private $active;
    
    public function __construct($options)
    {
        $this->options = $options;
        $this->db = NULL;
        $this->active = FALSE;
    }
    
    function DB()
    {
        if (!$this->db) $this->db = new CanonDB($this->options);
        return $this->db;
    }
    
    public function SetUpRules()
    {
        if ($this->active) return;
        
        $placeholder = $this->options->Get('placeholder');
        if ($placeholder) {
            global $wp;

            $wp->add_query_var('clid');
            $wp->add_query_var('clpage');

            foreach (['person','branch','award','event','reign','op'] as $page) {
                $this->addRule($placeholder,$page,'\d+');
                $this->addRule($placeholder,$page,'list');
            }
            $this->addRule($placeholder,'recommend','\d+');
            $this->addRule($placeholder,'current','crown');
            $this->addRule($placeholder,'current','gentry');
            $this->addRule($placeholder,'provost');

            if ($this->active) flush_rewrite_rules();
        }        
    }                     

    private function addRule($pageid, $page, $pattern = NULL)
    {
        add_rewrite_rule($pattern ? sprintf('^%s(?:/(%s))?$',$page,$pattern) : sprintf('^%s$',$page),
                         sprintf('index.php?page_id=%d&clpage=%s&clid=$matches[1]',$pageid,$page),
                         'top');
        $this->active = TRUE;
    }

    public function TidyRules()
    {
        if (!$this->active) return;

        flush_rewrite_rules();
        $this->active = FALSE;
    }

    public function ID()
    {
        return get_query_var('clid');
    }
    
    public function Page()
    {
        return get_query_var('clpage');
    }
    
    public function Title($title)
    {
        $plurals = ['person' => 'People',
                    'branch' => 'Branches',
                    'award'  => 'Awards',
                    'event'  => 'Events',
                    'reign'  => 'Reigns'];
                    
        $page = $this->Page();
        $id = $this->ID();
        
        switch ($page) {
            case 'person':
            case 'branch':
            case 'award':
            case 'event':
            case 'reign':
                if (+$id > 0) return $this->DB()->GetName($page,$id) ?: $title;
                return sprintf('List of %s',$plurals[$page]);
            case 'op':
                return 'Order of Precedence';
            case 'recommend':
                return 'Award Recommendation';
            case 'current':
                return sprintf('Current %s', $id == 'crown' ? 'Crown' : 'Landed Gentry');
            case 'provost':
                return "Provost's Report";
            default:
                return $title;
        }
    }
}
