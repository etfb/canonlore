<?php # canondb.php

require_once 'db.php';

class CanonDB
{
    private $db;
    
    public function __construct($options)
    {
        $this->db = new DB($options->Dir().'db.ini', 'CANON');
    }
 
    public function GetName($type,$id)
    {
        $this->db->Call(sprintf('get_%s_name',$type),
                        ['id' => _INTEGER($id)]);
        return $this->db->ReadValue();
    }
    
    public function GetPersonList()
    {
        $this->db->Call('get_person_list',[]);
        return $this->db->ReadSet();
    }

    public function GetBranchList()
    {
        $this->db->Call('get_branch_list',[]);
        return $this->db->ReadSet();
    }
    
    public function GetAwardList()
    {
        $this->db->Call('get_award_list',[]);
        return $this->db->ReadSet();
    }
    
    public function GetEventList()
    {
        $this->db->Call('get_event_list',[]);
        return $this->db->ReadSet();
    }
    
    public function GetReignList()
    {
        $this->db->Call('get_reign_list',[]);
        return $this->db->ReadSet();
    }
    
    public function GetOPList()
    {
        return [];
    }
    
    public function GetCurrentHats($iscrown)
    {
        return [];
    }
    
    public function GetPerson($id)
    {
        $this->db->Call('get_person',
                         ['id' => _INTEGER(+$id)]);
        
        return ['person'    => $this->db->ReadSingleRow(),
                'aliases'   => $this->db->ReadSet(),
                'awards'    => $this->db->ReadSet(),
                'hats'      => $this->db->ReadSet()];
    }
    
    public function GetBranch($id)
    {
        $this->db->Call('get_branch',
                         ['id' => _INTEGER(+$id)]);
        
        return ['branch'    => $this->db->ReadSingleRow(),
                'history'   => $this->db->ReadSet(),
                'subs'      => $this->db->ReadSet(),
                'res'       => $this->db->ReadSet(),
                'op'        => $this->db->ReadSet(),
                'events'    => $this->db->ReadSet(),
                'hats'      => $this->db->ReadSet()];
    }
    
    public function GetAward($id)
    {
        $this->db->Call('get_award',
                         ['id' => _INTEGER(+$id)]);
        
        return ['award'         => $this->db->ReadSingleRow(),
                'recipients'    => $this->db->ReadSet()];
    }
    
    public function GetEvent($id)
    {
        $this->db->Call('get_event',
                         ['id' => _INTEGER(+$id)]);
        
        return ['event'    => $this->db->ReadSingleRow(),
                'presiding'     => $this->db->ReadSet(),
                'recipients'    => $this->db->ReadSet()];
    }
    
    public function GetReign($id)
    {
        $this->db->Call('get_reign',
                         ['id' => _INTEGER(+$id)]);
        
        return ['reign'    => $this->db->ReadSingleRow(),
                'recipients'    => $this->db->ReadSet()];
    }
    
    private function sortby($key, $array) 
    {
        usort($array, function ($a,$b) use ($key) { return mb_strcasecmp($a[$key],$b[$key],'UTF-8'); });
        return $array;
    }    
}


function mb_strcasecmp($str1, $str2, $encoding = null) 
{
    if (null === $encoding) { $encoding = mb_internal_encoding(); }
    return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
}