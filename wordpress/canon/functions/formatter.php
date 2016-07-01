<?php # formatter.php

class Block
{
    private $html;
    private $summary;
    private $name;
    
    public function __construct()
    {
        $this->html = [];
        $this->summary = NULL;
        $this->name = NULL;
    }

    public function _($line)
    {
        $this->html[] = $line;
    }

    public function BeginSummary()
    {
        $this->summary = [];
    }
    
    public function Summarise($heading, $data)
    {
        if ($data == '' || $data === NULL) return;
        $this->summary[] = [$heading ?: '', $data];
    }
    
    public function EndSummary()
    {
        $this->_('<table class="summary">');
        $this->_('<tbody>');
        foreach ($this->summary as $detail) {
            list($caption,$text) = $detail;
            $this->_(sprintf('<tr><th>%s</th><td>%s</td></tr>',$caption,$text));
        }
        $this->_('</tbody>');
        $this->_('</table>');
    }
    
    public function HTML()
    {
        return join("\n", $this->html);
    }

    public function DIAGNOSTIC($title,$object)
    {
        $this->_(GetDiagnostic($title,$object));
    }
        
    public function AddTable($table, $class, $grouped = FALSE)
    {
        if ($grouped) $last = NULL;
        
        $this->_(sprintf('<table class="lined %s">',$class));
        $this->_('<thead>');
        $this->_('<tr>');
        foreach (array_keys($table[0]) as $i => $column) $this->_(sprintf('<th class="col-%d">%s</th>',$i+1,$this->Safe($column)));
        $this->_('</tr>');
        $this->_('</thead>');

        $this->_('<tbody>');
        foreach ($table as $row) {
            $values = array_values($row);
            if ($grouped && $last != NULL && $last != $values[0]) {
                $this->_('<tr class="new">');
            } else {
                $this->_('<tr>');
            }
            $last = $values[0];
            foreach ($values as $i => $value) $this->_(sprintf('<td class="col-%d">%s</td>',$i+1,$value));
            $this->_('</tr>');
        }
        $this->_('</tbody>');
        $this->_('</table>');
    }
        
    public function AddLinkList($list, $multicol = TRUE)
    {
        $this->_(sprintf('<div class="%s list">',$multicol ? 'multicol' : 'onecol'));
        foreach ($list as $item) {
            $this->_(sprintf('<p>%s</p>',$item));
        }
        $this->_('</div>');
    }
    
    public function AddListTabs($tabs)
    {
        $this->BeginTabSet(array_keys($tabs));

        foreach (array_keys($tabs) as $i => $label) {
            $page = $tabs[$label];
            $this->BeginTab($i);
            if ($page) $this->AddLinkList($page);
        }
        
        $this->EndTabSet();
    }
    
    public function AddLetteredTabs($list)
    {
        $labels = ['A'     => 'A',
                   'BC'    => 'BC',
                   'DEF'   => 'DEF',
                   'GHI'   => 'GHI',
                   'JKL'   => 'JKL',
                   'MNO'   => 'MNO',
                   'PQR'   => 'PQR',
                   'STU'   => 'STU',
                   'V-Z'   => 'VWXYZ',
                   'Other' => ''];
 
        $tabs = [];
        foreach (array_keys($labels) as $label) $tabs[$label] = [];

        uksort($list, 'strcasecmp');
        
        foreach ($list as $name => $item) {
            $initial = strtoupper($name[0]);
            $label = 'Other';
            foreach ($labels as $l => $set) {
                if (strpos($set,$initial) !== FALSE) {
                    $label = $l;
                    break;
                }
            }
            $tabs[$label][] = $item;
        }
        
        $this->AddListTabs($tabs);
    }

    public function AddCalendar($years)
    {
        $monthnames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $all = [];

        ksort($years);

        $latest = NULL;
        $earliest = NULL;
        foreach ($years as $year => $days) {
            if ($earliest == NULL) $earliest = $year;
            $latest = $year;
            $all[$year] = [];
            foreach ($days as $ymd => $day) {
                preg_match('/^\d+-(\d+)-\d+$/',$ymd,$matches);
                $month = +$matches[1];
                $all[$year][$month] = array_merge(@$all[$year][$month] ?: [],$day);
            }
        }
        
        $this->_(sprintf('<table id="canon-calendar" earliest-year="%d" latest-year="%d">',$earliest,$latest));
        $this->_('<thead>');
        $this->_('<tr class="ui-widget-header">');
        $this->_('<th><button class="prev">&lt;&lt;</button></th>');
        $this->_(sprintf('<th><button class="year">%d</button></th>',$latest));
        $this->_('<th><button class="next">&gt;&gt;</button></th>');
        $this->_('</tr>');
        $this->_('</thead>');
        foreach ($all as $year => $months) {
            $this->_(sprintf('<tbody year="%d">',$year));
            foreach (range(1,12,3) as $row) {
                $this->_('<tr>');
                foreach (range($row,$row+2) as $month) $this->_(sprintf('<th>%s</th>',$monthnames[$month-1]));
                $this->_('</tr>');
                $this->_('<tr>');
                foreach (range($row,$row+2) as $month) {
                    $this->_('<td>');
                    if (@$months[$month]) {
                        $this->AddLinkList($months[$month],FALSE);
                    }
                    $this->_('</td>');
                }
                $this->_('</tr>');
            }
            $this->_('</tbody>');
        }
        $this->_('</table>');
    }

    public function AddAccordion($tables,$args)
    {
        $this->_('<div class="accordion">');
        foreach ($tables as $heading => $table) {
            $this->_(sprintf('<h3>%s</h3>',$heading));
            $this->_('<div>');
            $this->AddTable($table,$args[$heading][0],$args[$heading][1]);
            $this->_('</div>');
        }
        $this->_('</div>');
    }
    
    public function Safe($str)
    {
        return htmlentities($str);
    }

    public function Ordinal($n)
    {
        switch ($n % 100) {
            case 11:
            case 12:
            case 13:         $suffix = 'th'; break;
            default:
                switch ($n % 10) {
                    case 1:  $suffix = 'st'; break;
                    case 2:  $suffix = 'nd'; break;
                    case 3:  $suffix = 'rd'; break;
                    default: $suffix = 'th'; break;
                }
        }
        
        return number_format($n) . $suffix;
    }

    public function Initial($str)
    {
        return strtoupper($str[0]) . substr($str,1);
    }
    
    public function PrettyDate($date)
    {
        $d = strtotime($date);
        if (!$d) return '';
        return date('j M Y',$d);
    }
    
    public function PrettyDateRange($from,$to,$since = 'Since',$until = 'Until',$unknown = '')
    {
        if ($to == $from) $to = NULL;
        if ($from && $to) return sprintf('%s to %s',$this->PrettyDate($from),$this->PrettyDate($to));
        if ($from) return sprintf('%s %s',$since,$this->PrettyDate($from));
        if ($to) return sprintf('%s %s',$until,$this->PrettyDate($to));
        return $unknown;
    }
    
    public function Link($type, $id, $name, $nothing = '-')
    {
        if (!$name) return $nothing;
        return sprintf('<a href="../../%s/%d">%s</a>',$type,$id,$name);
    }

    public function Para($text)
    {
        $this->_('<p>');
        $this->_($text);
        $this->_('</p>');
    }

    public function FixMePara($entity,$id)
    {
        $this->Para(sprintf('Please check this information and <a %s>notify the Canon Herald of corrections</a>.',
                    $this->FixMeLink($entity,$id)));
    }

    public function FixMeLink($entity,$id = NULL)
    {
        return sprintf('title="Fix Me!" href="#" class="canon-fixme" canon-type="%s" canon-id="%s"',$entity ?: 'general', $id);
    }
    
    public function RecommendURL($id)
    {
        return sprintf('../../recommend/%d',$id);
    }
    
    public function BeginTabSet($tabs)
    {
        $this->_('<div id="tabs">');

        $this->_('<ul>');
        foreach ($tabs as $id => $label) {
            $this->_(sprintf('<li><a href="#tab-%s">%s</a></li>',$id,$label));
        }
        $this->_('</ul>');
        
        $this->inTab = FALSE;
    }
    
    public function BeginTab($id)
    {
        if ($this->inTab) $this->_('</div>');
        $this->_(sprintf('<div id="tab-%s">',$id));
        $this->inTab = TRUE;
    }
    
    public function EndTabSet()
    {
        if ($this->inTab) $this->_('</div>');
        $this->_('</div>');
    }
}

class Formatter
{
    public function FormatPersonList($persons)
    {
        $b = new Block();

        $list = [];
        foreach ($persons as $person) {
            $list[$person['sca']] = $person['really'] ? sprintf('%s - see %s', $person['sca'], $b->Link('person',$person['id'], $person['really']))
                                                      : $b->Link('person',$person['id'], $person['sca']);
        }
        
        $b->AddLetteredTabs($list);
        return $b->HTML();
    }
    
    public function FormatBranchList($branches)
    {
        $b = new Block();

        $list = [];
        foreach ($branches as $branch) {
            $list[$branch['name']] = sprintf('%s of %s',$branch['type'],$b->Link('branch',$branch['id'],$branch['name']));
        }

        $b->AddLetteredTabs($list);
        return $b->HTML();
    }
    
    public function FormatAwardList($awards,$here)
    {
        $b = new Block();

        $list = [$here => [], 'Elsewhere' => []];
        foreach ($awards as $award) {
            $row = ['Award' => $b->Link('award',$award['id'],$award['name']),
                    'Acronym' => $award['acronym'] ? $award['acronym'] : '',
                    'Realm' => $award['branch_name'] ? $b->Link('branch',$award['branch_id'],$award['branch_name']) : 'SCA'];

            $where = $award['branch_name'] == $here || !$award['branch_name'] ? $here : 'Elsewhere';
            $list[$where][] = $row;
        }

        $b->AddAccordion($list, [$here => ['',FALSE],
                                 'Elsewhere' => ['',FALSE]]);
        return $b->HTML();
    }
    
    public function FormatEventList($events)
    {
        $b = new Block();
        
        $years = [];
        foreach ($events as $event) {
            $from = $event['from'];
            preg_match('/^(\d+)-\d+-(\d+)$/',$from,$matches);
            $year = +$matches[1]; 
            $day = +$matches[2];
            $years[$year][$from][] = sprintf('%d. %s',$day,$b->Link('event',$event['id'],$event['name']));
        }
        ksort($years);
        $b->AddCalendar($years); 
            
        return $b->HTML();
    }
    
    public function FormatReignList($reigns, $here)
    {
        $b = new Block();
        $list = [$here => [], 'Baronies' => [], 'Kingdoms' => []];
        foreach ($reigns as $reign) {
            $realm = $reign['realm'];
            $type = $reign['type'];
            if ($realm == $here) {
                $group = $here;
                $colhead = NULL;
                $hats = 'Crown';
            } else if ($type == 'barony') {
                $group = 'Baronies';
                $colhead = 'Barony';
                $hats = 'Gentry';
            } else {
                $group = 'Kingdoms';
                $colhead = 'Kingdom';
                $hats = 'Crown';
            }
            
            $row = [];
            if ($colhead) $row[$colhead] = $realm;
            $row['Duration'] = $b->PrettyDateRange($reign['from'],$reign['to']);
            $row[$hats] = $b->Link('reign',$reign['id'],$reign['name']);
            
            $list[$group][] = $row;
        }
        
        $b->AddAccordion($list,[$here => ['',FALSE],
                                'Baronies' => ['',TRUE],
                                'Kingdoms' => ['',TRUE]]);
        return $b->HTML();
    }
    
    public function FormatOPList($op)
    {
        return '<b>Format OP List</b>';
    }
    
    public function FormatCurrentHats($hats)
    {
        return '<b>Format Current Hats</b>';
    }
    
    public function FormatRecommendation($person)
    {
        return '<b>Format Recommendation</b>';
    }
    
    public function FormatProvostReport()
    {
        return '<b>Format Provost Report</b>';
    }
    
    public function FormatPerson($full)
    {
        $b = new Block();
        $person = $full['person'];

        list($place,$awards) = $this->opPosition($b,$person['order'],$person['op_count'],$person['op_award'],$person['highest_award']);

        $b->BeginSummary();
        $b->Summarise('Resides', $b->Link('branch',$person['branch_id'],$person['branch_name']));
        if ($place) $b->Summarise('Place in <abbr title="Order of Precedence">OP</abbr>', sprintf('%s (%s)',$place,$awards));
        $b->Summarise('Mundane name', $person['mundane'] ? 'Recorded' : 'Unknown');
        $this->summariseAliases($b, $full['aliases']);
        $this->summariseHats($b, $full['hats']);
        $b->EndSummary();

        $this->addAwardsTable($b,$full['awards']);
        
        $b->FixMePara('person',$person['id'],$person['sca']);
        
        if ($person['tracking'] == 'normal')
            $b->Para(sprintf('Do you believe %s is worthy of an award you don\'t see listed? '
                            .'Please <a href="%s">recommend %s for an award</a>.',
                             $person['sca'],
                             $b->RecommendURL($person['id']),
                             $person['sca']));
                         
        return $b->HTML();
    }

    public function FormatBranch($full)
    {
        $b = new Block();
        $branch = $full['branch'];
        
        $b->BeginSummary();
        $b->Summarise('Location', $branch['location']);
        $b->Summarise('Web site', $branch['url'] ? sprintf('<a href="%s">%s</a>',$branch['url'],$branch['url']) : NULL);

        if ($full['history'])
            foreach ($full['history'] as $row) {
                $b->Summarise($b->PrettyDateRange($row['from'],$row['to'],'Since','Until','Time Unknown'),
                              !$row['parent_id'] ? $row['type'] : sprintf('%s in %s', $b->Initial($row['type']), $b->Link('branch',$row['parent_id'],$row['parent_name'])));
            }

        $b->EndSummary();

        $tabs = [];
        if ($full['subs']) $tabs['subs'] = 'Sub-branches';
        if ($full['res']) $tabs['res'] = 'Residents';
        if ($full['op']) $tabs['op'] = 'OP';
        if ($full['events']) $tabs['events'] = 'Events';
        if ($full['hats']) $tabs['hats'] = 'Landed Gentry';
           
        if ($tabs) {
            $b->BeginTabSet($tabs);
            
            if ($full['subs']) {
                $subs = [];
                foreach ($full['subs'] as $sub) {
                    $subs[] = sprintf('%s of %s',$sub['type'],$b->Link('branch',$sub['id'],$sub['name']));
                }
                $b->BeginTab('subs');
                $b->AddLinkList($subs);
            }
            
            if ($full['res']) {
                $residents = [];
                foreach ($full['res'] as $resident) {
                    $residents[] = $b->Link('person',$resident['id'],$resident['sca']);
                }
                $b->BeginTab('res');
                $b->AddLinkList($residents);
            }
            
            if ($full['op']) {
                $op = [];
                foreach ($full['op'] as $row) {
                    list($place,$awards) = $this->opPosition($b,$row['order'],$row['count'],$row['highest_id'],$row['highest_award']);
                    if ($place) 
                        $op[] = ['Place in OP' => $place,
                                 'Name'        => $b->Link('person',$row['id'],$row['sca']),
                                 'Awards'      => $awards];
                }
                $b->BeginTab('op');
                $b->AddTable($op,'op dated'); // pretend it's dated so we get a right-aligned first column
            }
            
            if ($full['events']) {
                $e = [];
                foreach ($full['events'] as $row) {
                    $from = $row['from'];
                    $to = $row['to'];
                    
                    $e[] = ['Date'      => $b->PrettyDateRange($from,$to,'',''),
                            'Event'     => $b->Link('event',$row['id'],$row['name']),
                            'Location'  => $row['location']];
                }
                $b->BeginTab('events');
                $b->AddTable($e,'events dated');
            }
            
            if ($full['hats']) {
                $h = [];
                foreach ($full['hats'] as $row) {
                    $from = $row['from'];
                    $to = $row['to'];
                    if ($to == $from) $to = NULL;
                    
                    $h[] = ['Date'      => $b->PrettyDateRange($from,$to),
                            'Gentry'    => $b->Link('reign',$row['id'],$row['name']),
                            'Sovereign' => sprintf('%s %s', $row['sov_title'], $b->Link('person',$row['sov_id'],$row['sov'])),
                            'Consort'   => $row['con'] ? sprintf('%s %s', $row['con_title'], $b->Link('person',$row['con_id'],$row['con'])) : NULL];
                }
                $b->BeginTab('hats');
                $b->AddTable($h,'hats dated');
            }

            $b->EndTabSet();
        }
        $b->FixMePara('branch',$branch['id'],$branch['name']);
        return $b->HTML();
    }

    public function FormatAward($full)
    {
        $b = new Block();
        $award = $full['award'];

        $b->BeginSummary();
        if ($award['branch_id']) $b->Summarise('From',$b->Link('branch',$award['branch_id'],$award['branch_name']));
        $b->Summarise('Abbreviation', $award['acronym']);
        $b->Summarise('Precedence', sprintf('%s level %d',$b->Initial($award['rank']),$award['precedence']));
        $b->Summarise('Status', $award['active'] ? 'Active' : 'Inactive');
        $b->EndSummary();

        $b->Para($award['description']);

        if ($full['recipients']) {
            $rec = [];
            foreach ($full['recipients'] as $row) {
                list($place,$awards) = $this->opPosition($b,$row['order'],$row['count'],$row['highest_id'],$row['highest_award']);
                $rec[] = ['Date'            => $b->PrettyDate($row['date']),
                          'Recipient'       => $b->Link('person',$row['person_id'],$row['person_name']),
                          'Royalty'         => $b->Link('reign',$row['hats_id'],$row['hats_name']),
                          'Event'           => $b->Link('event',$row['event_id'],$row['event_name']),
                          'Place in OP'     => $place];
            }
            $b->AddTable($rec,'rec dated');
        }
        
        return $b->HTML();
    }

    public function FormatEvent($full)
    {
        $b = new Block();
        $event = $full['event'];

        $b->BeginSummary();
        $b->Summarise('Date',$b->PrettyDateRange($event['from'],$event['to'],''));
        $b->Summarise('Location',$event['location']);
        $b->Summarise('Host',$b->Link('branch',$event['host_id'],$event['host_name']));
        
        if ($full['presiding'])
            foreach ($full['presiding'] as $row)
                $b->Summarise('Presiding',$b->Link('reign',$row['hats_id'],$row['hats_name']));
                
        $b->EndSummary();
        
        if ($full['recipients']) {
            $rec = [];
            $dated = ($event['from'] != $event['to']);
            foreach ($full['recipients'] as $row) {
                $add = [];
                if ($dated) $add['Date'] = $b->PrettyDate($row['date']);
                $add['Recipient'] = $b->Link('person',$row['person_id'],$row['person_name']);
                $add['Award'] = $b->Link('award',$row['award_id'],$row['award_name']);

                $rec[] = $add;
            }
            $b->AddTable($rec,'rec' . ($dated ? ' dated' : ''));
        }

        return $b->HTML();
    }

    public function FormatReign($full)
    {
        $b = new Block();
        $reign = $full['reign'];

        switch ($reign['type']) {
            case 'canton':
            case 'college':
            case 'shire':
            case 'region':
                return;
            case 'barony':
                list($type,$won,$crowned,$abdicated) = ['Barony','Elected','Invested','Stepped down']; 
                break;
            case 'crown-principality':
                list($type,$won,$crowned,$abdicated) = ['Crown Principality','Won Viceregal Tourney','Invested','Stepped down']; 
                break;
            case 'principality':
                list($type,$won,$crowned,$abdicated) = ['Principality','Won Coronet Tourney','Invested','Stepped down']; 
                break;
            case 'kingdom':
                list($type,$won,$crowned,$abdicated) = ['Kingdom','Won Crown Tourney','Crowned','Stepped down']; 
                break;
        }
        
        $b->BeginSummary();
        $b->Summarise($type,$b->Link('branch',$reign['realm_id'],$reign['realm_name']));
        $b->Summarise($reign['sov_title'],$b->Link('person',$reign['sov_id'],$reign['sov']));
        $b->Summarise($reign['con_title'],$b->Link('person',$reign['con_id'],$reign['con']));
        $b->Summarise($won,$b->PrettyDate($reign['won']));
        $b->Summarise($crowned,$b->PrettyDate($reign['from']));
        $b->Summarise($abdicated,$b->PrettyDate($reign['to']));
        $b->EndSummary();

        if ($full['recipients']) {
            $rec = [];
            foreach ($full['recipients'] as $row) {
                $rec[] = ['Date'      => $b->PrettyDate($row['date']),
                          'Recipient' => $b->Link('person',$row['person_id'],$row['person_name']),
                          'Award'     => $b->Link('award',$row['award_id'],$row['award_name']),
                          'Event'     => $b->Link('event',$row['event_id'],$row['event_name'])];
            }
            $b->AddTable($rec,'rec dated');
        }

        return $b->HTML();
    }

    private function opPosition($b, $order, $count, $id, $name)
    {
        if ($order !== NULL) {
            $place = $b->Safe($b->Ordinal($order));
            $awards = '';
            if ($count) {
                $awards = $b->Link('award',$id,$name);
                switch ($count) {
                    case 1:
                        break;
                    case 2:
                        $awards .= ' and one other';
                        break;
                    default:
                        $awards .= sprintf(' and %d others',$count-1);
                        break;
                }
            }
            return [$place,$awards];
        } else {
            return NULL;
        }
    }
    
    private function summariseAliases($b, $aliases)
    {
        $a = [];
        $knownlroa = [];
        foreach ($aliases as $alias) {
            $sca = $alias['sca'];

            $lroa = NULL;
            if (($l = $alias['lroa']) && !array_key_exists($l,$knownlroa)) {
                $knownlroa[$l] = TRUE;
                $lroa = sprintf('<a href="http://lochac.sca.org/LRoA/index.php?page=individual&id=%d">Roll of Arms</a>',$l);
            }
            
            $registered = ($r = $alias['registered']) ? date('M Y',strtotime($r.'-01')) : NULL;

            if ($alias['primary']) {
                $b->Summarise('Registered',$registered);
                $b->Summarise('Arms',$lroa);
            } else {
                $bits = [];
                if ($registered) $bits[] = 'Registered ' . $registered;
                if ($lroa) $bits[] = $lroa;
                if ($bits) {
                    $a[] = sprintf('%s (%s)',$sca, join(', ',$bits));
                } else {
                    $a[] = $sca;
                }
            }
        }
        if ($a) $b->Summarise('Also/formerly known as', join(', ',$a));
    }
    
    private function summariseHats($b, $hats)
    {
        if ($hats)
            foreach ($hats as $reign) {
                $b->Summarise($b->PrettyDateRange($reign['from'],$reign['to']),
                              sprintf('%s of %s %s', $b->Link('reign',$reign['id'],sprintf('Ruled as %s',$reign['title'])),
                                                     $b->Link('branch',$reign['realm_id'],$reign['realm']),
                                                     ($c = $reign['consort_id']) ? 'with ' . $b->Link('person',$c,$reign['consort']) : 'alone'));
            }
    }

    private function addAwardsTable($b, $awards)
    {
        if ($awards)
        {
            $a = [];
            foreach ($awards as $award) {
                $a[] = ['Date'     => $b->PrettyDate($award['date']),
                        'Award'    => $b->Link('award',$award['award_id'],$award['award']),
                        'Given by' => $b->Link('reign',$award['hats_id'],$award['hats']),
                        'Event'    => $b->Link('event',$award['event_id'],$award['event'])];
            }
            $b->AddTable($a,'awards dated');
        }
    }
}