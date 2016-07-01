<?php # options.php

class Options
{
    private $options;
    private $url;
    private $dir;
    
    public function __construct($base)
    {
        $this->url = plugin_dir_url($base);
        $this->dir = plugin_dir_path($base);

        $o = get_option('canon_options');
        if (!$o || !is_array($o) || TRUE) $o = [];
        $this->options = array_merge($this->Defaults(),$o);
        $this->save();
    }
    
    public function Dir()
    {
        return $this->dir;
    }
    
    public function URL()
    {
        return $this->url;
    }
    
    public function Defaults()
    {
        $defaults = ['placeholder' => 19,
                     'here'        => 'Lochac',
                     'etc' => 'fill in other options'];

        return $defaults;
    }
    
    public function Options()
    {
        return $this->options;
    }
    
    public function Get($option)
    {
        return $this->options[$option];
    }
    
    public function Set($option, $value)
    {
        $this->options[$option] = $value;
        $this->save();
    }
    
    private function save()
    {
        update_option('canon_options',$this->options);
    }
}
