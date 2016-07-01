<?php # diagnostic.php

global $DEBUG_FILENAME;
$DEBUG_FILENAME = 'debug.log';

// 1.00 Basic version, written for Tothy
// 1.01 Added types and special handling for DOMDocument
// 1.02 Added GetDiagnostic and the $wantcss arg
// 1.03 Added error reporting defaults

error_reporting(E_ALL|E_STRICT|E_DEPRECATED|E_NOTICE);

function diagnostic_css()
{
    static $sent = FALSE;

    if ($sent) return '';
    $sent = TRUE;
    return "<style>
table.diagnostic,
table.dump
{
    border-collapse: collapse;
    border: solid 1px black;
    font: Verdana;
    width: 100%;
    white-space: pre-wrap;
}

table.diagnostic td,
table.dump td,
table.diagnostic th,
table.dump th
{
    border: solid 1px black;
    text-align: left;
    padding: 5px;
    vertical-align: top;
}

table.diagnostic th,
table.dump th
{
    background: silver;
    font-weight: normal;
    font-size: 80%;
}

pre.diagnostic
{
    border: solid 2px red;
    background: #ffffee;
    padding: 1em;
    font-family: Verdana;
    font-size: 8pt;
}

pre.diagnostic h1
{
    padding: 0;
    margin: 0;
}

table.diagnostic td, table.dump td { background: #ffcccc; border-color: #663333; }
table.diagnostic table.diagnostic td { background: #ffddaa; border-color: #995533; }
table.diagnostic table.diagnostic table.diagnostic td { background: #ffffaa; border-color: #666633; }
table.diagnostic table.diagnostic table.diagnostic table.diagnostic td { background: #ccffcc; border-color: #336633; }
table.diagnostic table.diagnostic table.diagnostic table.diagnostic table.diagnostic td { background: #ccccff; border-color: #333366; }
table.diagnostic table.diagnostic table.diagnostic table.diagnostic table.diagnostic table.diagnostic td { background: #ffccff; border-color: #663366; }

</style>";
}

function GetDiagnostic($title, $object)
{
    $result = diagnostic_css();
    $result .= "<pre class=\"diagnostic\">";
    if ($title) $result .= "<h1>{$title}</h1>";
    $result .= MakeTable($object);
    $result .= '</pre>';

    return $result;
}

function MakeTable($object, $depth=0)
{
    if ($depth > 20) {
        return '<b>RECURSION</b>';
    } elseif ($object instanceof DOMDocument) {
        return htmlentities($object->saveXML());
    } elseif (is_array($object) || is_object($object)) {
        //$type = is_object($object) ? get_class($object) : gettype($object);
        if (is_object($object)) $object = (array) $object;
        $table = '<table class="diagnostic">';
        //if ($type != 'array') $table .= sprintf('<thead><tr><th colspan="2">%s</th></tr></thead>',$type);
        $table .= '<tbody>';
        foreach ($object as $key => $value) {
            $table .= sprintf('<tr><td width="25%%"><b>%s</b></td><td width="75%%">%s</td></tr>',htmlentities($key),MakeTable($value,$depth+1));
        }
        $table .= '</tbody></table>';
        return $table;
    } else {
        return htmlentities(var_export($object,TRUE));
    }
}

function DIAGNOSTIC(/*...*/)
{
    $args = func_get_args();
    if (count($args) == 1) { // caller doesn't want a title
        $title = NULL;
        $object = $args[0];
    } else {
        $title = $args[0];
        $object = $args[1];
    }
    print GetDiagnostic($title, $object);
}

function ArrayToTable($array,$alt_titles = NULL)
{
    $result = '<table class="dump">';

    if ($array) {
        $result .= '<thead><tr>';
        foreach ($array[0] as $key => $value) {
            $title = $alt_titles ? $alt_titles[$key] : $key;
            $result .= "<th>{$title}</th>";
        }
        $result .= '</thead><tbody>';
        foreach ($array as $i => $row) {
            $class = $i % 2 ? 'odd' : 'even';
            $result .= "<tr class=\"{$class}\">";
            foreach ($row as $element) {
                $result .= "<td>" . htmlentities($element) . "</td>";
            }
            $result .= "</tr>";
        }
        $result .= "</tbody>";
    } else {
        $result .= "<tbody><tr><td><b>No Result</b></td></tr></tbody>";
    }
    $result .= "</table>";

    return $result;
}

function DUMP_TABLE($array)
{
    print diagnostic_css() . ArrayToTable($array);
}

function DEBUG_LOG($subject,$data = [])
{
    global $DEBUG_FILENAME;
    
    $message = sprintf("%s - %s\n\n%s\n---------------------------------------------\n\n", 
                       date('r'), $subject, var_export($data,TRUE));
    $f = fopen($DEBUG_FILENAME, 'a');
    fwrite($f,$message);
    fclose($f);
}    
