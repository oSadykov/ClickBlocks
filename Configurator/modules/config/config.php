<?php

namespace ClickBlocks\Configurator;

class Config extends Module
{
  private $defaultConfiguration = [
                'debugging' => true,
                'logging' => true,
                'templateDebug' => 'Framework/tpl/debug/debug.tpl',
                'autoload' => ['search' => true,
                               'unique' => true,
                               'classmap' => 'classmap.php',
                               'timeout' => 300,
                               'mask' => '/.+\.php\z/i'],
                'cache' => ['type' => 'file',
                            'directory' => 'cache',
                            'gcProbability' => 33.333],
                'dirs' => ['application' => 'Application',
                           'framework' => 'Framework',
                           'logs' => 'Temporary/_logs',
                           'cache' => 'Temporary/_cache',
                           'temp' => 'Temporary/_temp',
                           'ar' => 'Application/_engine/ar',
                           'orm' => 'Application/_engine/orm',
                           'js' => 'Application/_includes/js',
                           'css' => 'Application/_includes/css',
                           'tpl' => 'Application/_includes/tpl',
                           'elements' => 'app/inc/tpl/elements'],
                'db' => ['logging' => true,
                         'log' => 'app/tmp/sql.log',
                         'cacheExpire' => 0,
                         'cacheGroup' => 'db'],
                'ar' => ['cacheExpire' => -1,
                         'cacheGroup' => 'ar'],
                'mvc' => ['locked' => false,
                          'unlockKey' => 'iwanttosee',
                          'unlockKeyExpire' => 108000,
                          'templateLock' => 'lib/tpl/bug.tpl'],
                'pom' => ['charset' => 'utf-8',
                          'prefix' => 'c',
                          'ppOpenTag' => '<!--{',
                          'ppCloseTag' => '}-->',
                          'cacheEnabled' => false]];

  private $defaults = ['app/core/config.php' => 1, 'app/core/.local.php' => 1];
  
  private $common = ['logging', 'debugging', 'dirs', 'templateDebug', 'templateBug', 'customDebugMethod', 'customLogMethod', 'autoload', 'cache', 'db', 'ar', 'mvc', 'pom'];
  
  public function init()
  {
    $cfgs['/Application/_config/config.ini'] = 0;
    // Loading of the local (development) application config.
    if (getenv('APP_ENV')) {
        $cfgs['/Application/_config/.local_' . getenv('APP_ENV') . '.ini'] = 1;
    } else {
        $cfgs['/Application/_config/.local.ini'] = 1;
    }
    $configs = []; $nums = [];
    foreach ($cfgs as $file => $editable) $configs[self::normalizePath($file)] = $editable;
    if (Configurator::isFirstRequest())
    {
      $n = 0;
      foreach ($configs as $file => $editable)
      {
        if (!file_exists($file))
        {
          $pinfo = pathinfo($file);
          if (!file_exists($pinfo['dirname'])) mkdir($pinfo['dirname'], 07775, true);
          if (strtolower($pinfo['extension']) == 'php') file_put_contents($file, '<?php' . PHP_EOL . PHP_EOL . 'return [];');
          else file_put_contents($file, '');
          $nums[] = $n;
        }
        $n++;
      }
    }
    Configurator::setConfigs($configs);
    if ($nums) foreach ($nums as $n) $this->saveConfig($this->defaultConfiguration, ['file' => $n], false);
  }
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'show':
        $n = isset($args['file']) ? (int)$args['file'] : 0;
        $file = $this->getConfigFile($n);
        if ($file === false) self::error('The configuration file of number ' . $n . ' does not exist.');
        else
        {
          if (Configurator::isCLI()) print_r(Configurator::getCB()->setConfig($file, null, true));
          else echo $this->renderConfig($file);
        }
        break;
      case 'save':
        if (Configurator::isCLI())
        {
          echo $this->getCommandHelp();
          break;
        }
        $cfg = $args['config'];
        $cfg['debugging'] = (bool)$cfg['debugging'];
        $cfg['logging'] = (bool)$cfg['logging'];
        $cfg['autoload']['search'] = (bool)$cfg['autoload']['search'];
        $cfg['autoload']['unique'] = (bool)$cfg['autoload']['unique'];
        if (isset($cfg['autoload']['timeout']) && (int)$cfg['autoload']['timeout'] > 0) $cfg['autoload']['timeout'] = (int)$cfg['autoload']['timeout'];
        if (isset($cfg['autoload']['directories'])) 
        {
          $cfg['autoload']['directories'] = json_decode($cfg['autoload']['directories'], true);
          if (!is_array($cfg['autoload']['directories'])) unset($cfg['autoload']['directories']); 
        }
        if (isset($cfg['autoload']['exclusions'])) 
        {
          $cfg['autoload']['exclusions'] = json_decode($cfg['autoload']['exclusions'], true);
          if (!is_array($cfg['autoload']['exclusions'])) unset($cfg['autoload']['exclusions']); 
        }
        if ($cfg['cache']['type'] == 'memory')
        {
          if (isset($cfg['cache']['servers'])) 
          {
            $cfg['cache']['servers'] = json_decode($cfg['cache']['servers'], true);
            if (!is_array($cfg['cache']['servers'])) unset($cfg['cache']['servers']);
          }
        }
        $cfg['mvc']['locked'] = (bool)$cfg['mvc']['locked'];
        $cfg['pom']['cacheEnabled'] = (bool)$cfg['pom']['cacheEnabled'];
        $cfg['db']['logging'] = (bool)$cfg['db']['logging'];
        if (isset($cfg['db']['cacheExpire'])) $cfg['db']['cacheExpire'] = (int)$cfg['db']['cacheExpire'];
        if (isset($cfg['ar']['cacheExpire'])) $cfg['ar']['cacheExpire'] = (int)$cfg['ar']['cacheExpire'];
        $props = isset($cfg['custom']) ? $cfg['custom'] : [];
        unset($cfg['custom']);
        foreach ($props as $prop => $value)
        {
          $cfg[$prop] = $value != '' ? json_decode($value, true) : '';
        }
        $this->saveConfig($cfg, $args);
        break;
      case 'update':
        $cfg = [];
        if (false !== $file = $this->getConfigFile(isset($args['file']) ? (int)$args['file'] : 0))
        {
          $cfg = Configurator::getCB()->setConfig($file, null, true);
          if (isset($args['property']) && is_array($args['property']))
          {
            for ($i = 0, $len = count($args['property']); $i < $len; $i += 2)
            {
              $c = &$cfg;
              foreach (explode('.', $args['property'][$i]) as $prop) $c = &$c[$prop];
              $c = $args['property'][$i + 1];
            }
          }
        }
        if ($this->saveConfig($cfg, $args) && Configurator::isCLI()) echo PHP_EOL . 'The configuration file has been successfully updated.' . PHP_EOL;
        break;
      case 'restore':
        if ($this->saveConfig($this->defaultConfiguration, $args) && Configurator::isCLI()) echo PHP_EOL . 'The configuration file has been successfully restored.' . PHP_EOL;
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    $cb = Configurator::getCB();
    $current = $cb->getConfig();
    $cfg = $cb->setConfig(key(Configurator::getConfigs()), null, true);
    $cb->setConfig($current, null, true);
    $data = ['configs' => array_keys(Configurator::getConfigs()),
             'editable' => (bool)current(Configurator::getConfigs()),
             'common' => $this->common,
             'cfg' => $cfg];
    return ['js' => 'config/js/config.js', 'html' => 'config/html/config.html', 'data' => $data];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to change one or more configuration properties or restore the entire config file to the default settings.

The use cases:

    1. cfg config show [--file FILE_NUMBER]
    
       Outputs the configuration data of the given configuration file.

    2. cfg config restore [--file FILE_NUMBER] 
    
       Restores the configuraton file of number FILE_NUMBER. The list of the configuration files is located in models/config/config.ini file.

    3. cfg config update [--file FILE_NUMBER] [--property PROPERTY_NAME PROPERTY_VALUE ...]  

       Changes value of the configuration property PROPERTY_NAME to new value PROPERTY_VALUE in the configuration file of number FILE_NUMBER.
       You can change more than one property at once.

HELP;
  }
  
  private function saveConfig(array $cfg, array $args, $render = true)
  {
    $n = isset($args['file']) ? (int)$args['file'] : 0;
    $file = $this->getConfigFile($n);
    if ($file && (!$render || Configurator::getConfigs()[$file]))
    {
      if (self::isPHPFile($file))
      {
        $res = '';
        $tokens = token_get_all(file_get_contents($file));
        foreach (array_reverse($tokens) as $i => $token) if ($token[0] == T_RETURN) break;
        $i = count($tokens) - $i - 1;
        foreach ($tokens as $j => $token)
        {
          if ($j == $i) break;
          $res .= is_array($token) ? $token[1] : $token;
        }
        $res .= 'return ' . $this->formArray($cfg, 8) . ';';
      }
      else
      {
        $res = $this->formINIFile($cfg);
      }
      file_put_contents($file, $res);
      if ($render && !Configurator::isCLI()) echo $this->renderConfig($file);
      return true;
    }
    self::error('The configuration file of number ' . $n . ' does not exist or cannot be modified.');
    return false;
  }
  
  private function formINIFile(array $a)
  {
    $tmp1 = $tmp2 = [];
    foreach ($a as $k => $v)
    {
      if (is_array($v))
      {
        $tmp = ['[' . $k . ']'];
        foreach ($v as $kk => $vv)
        {
          if (!is_numeric($vv))
          {
            if (is_array($vv)) $vv = '"' . addcslashes(json_encode($vv), '"') . '"';
            else if (is_bool($vv)) $vv = $vv ? 1 : 0;
            else if ($vv === null) $vv = '""';
            else if (is_string($vv)) $vv = '"' . addcslashes($vv, '"') . '"';
          }
          $tmp[] = str_pad($kk, 30) . ' = ' . $vv;
        }
        $tmp2[] = PHP_EOL . implode(PHP_EOL, $tmp);
      }
      else
      {
        if (!is_numeric($v))
        {
          if ($v === null) $v = '""';
          else if (is_bool($v)) $v = $v ? 1 : 0;
          else if (is_string($v)) $v = '"' . addcslashes($v, '"') . '"';
        }
        $tmp1[] = str_pad($k, 30) . ' = ' . $v;
      }
    }
    return implode(PHP_EOL, array_merge($tmp1, $tmp2));
  }
  
  private function formArray(array $a, $indent = 1)
  {
    $tmp = []; $isInteger = array_keys($a) === range(0, count($a) - 1);
    foreach ($a as $k => $v)
    {
      if (is_string($k)) $k = "'" . addcslashes($k, "'") . "'";
      if (!is_numeric($v))
      {
        if (is_array($v)) $v = $this->formArray($v, $indent + strlen($k) + 5);
        else if (is_string($v)) $v = "'" . addcslashes($v, "'") . "'";
        else if (is_bool($v)) $v = $v ? 'true' : 'false';
        else if ($v === null) $v = 'null';
      }
      $tmp[] = $isInteger ? $v : $k . ' => ' . $v;
    }
    return '[' . implode(',' . PHP_EOL . str_repeat(' ', $indent), $tmp) . ']';
  }
  
  private function getConfigFile($n)
  {
    $files = array_keys(Configurator::getConfigs());
    return isset($files[$n]) ? $files[$n] : false;
  }
  
  private function renderConfig($file)
  {
    $data = ['cfg' => Configurator::getCB()->setConfig($file, null, true), 
             'editable' => Configurator::getConfigs()[$file],
             'common' => $this->common];
    return self::render(__DIR__ . '/html/settings.html', $data);
  }
}