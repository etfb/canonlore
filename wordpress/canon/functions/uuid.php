<?php # uuid-generator.php

// Generate a type 4 (random) UUID in the form "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" (note: no braces)

function GenerateUUID()
{
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                 randword(), randword(),
                 randword(),
                 0x4000 | randword(0x0FFF),
                 0x8000 | randword(0x3FFF),
                 randword(), randword(), randword());
}

function randword($max = 0xFFFF)
{
  return mt_rand(0,$max);
}

/* Test:

foreach (range(1,10) as $i) {
  print "<tt>" . GenerateUUID() . "</tt><br>";
}
*/

?>
