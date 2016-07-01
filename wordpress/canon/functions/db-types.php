<?php # db-types.php

const _IN = 0x01;
const _OUT = 0x10;
const _INOUT = 0x11; // _IN|_OUT

class DBType
{
  protected $direction;
  protected $value;

  public function __construct($direction, $value)
  {
    $this->value = $value;
    $this->direction = $direction;
  }

  public function Quote($mysqli) { return $this->value; }
  public function GetValue() { return $this->value; }
  public function GetDirection() { return $this->direction; }
}

class DBStringType extends DBType
{
  public function Quote($mysqli)
  {
    return sprintf("'%s'", $mysqli->escape_string($this->value));
  }
}

class DBDateTimeType extends DBType
{
  public function Quote($mysqli)
  {
    return sprintf("'%s'", date('Y-m-d H:i:s', $this->value));
  }
}

class DBIntegerType extends DBType
{
  public function Quote($mysqli)
  {
    return sprintf("%1.0f", $this->value);  // %d can't handle integers above 2^31-1, apparently. WTF?
  }
}

class DBFloatType extends DBType
{
  public function Quote($mysqli)
  {
    return sprintf("%f", +$this->value);
  }
}

class DBBooleanType extends DBType
{
  public function Quote($mysqli)
  {
    return $this->value ? '1' : '0';
  }
}

function _STRING($value, $direction = _IN) { return new DBStringType($direction,$value); }
function _DATETIME($value, $direction = _IN) { return new DBDateTimeType($direction,$value); }
function _INTEGER($value, $direction = _IN) { return new DBIntegerType($direction,$value); }
function _FLOAT($value, $direction = _IN) { return new DBFloatType($direction,$value); }
function _BOOLEAN($value, $direction = _IN) { return new DBBooleanType($direction,$value); }
