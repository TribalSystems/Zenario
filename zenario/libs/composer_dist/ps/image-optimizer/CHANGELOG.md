CHANGELOG
=========

* 1.2.0 (2018-04-11)

  * [#46] Add support for svg files (using svgo library)

* 1.1.3 (2018-04-09)

  * [#53, #54, #50] Compatibility with Symfony 4

* 1.1.2 (2017-07-21)

  * [#44] Throw exception for Permissions Denied

* 1.1.1 (2017-06-28)

  * [#30] Fix "Command not found" issue related to open_basedir
  
* 1.1.0 (2017-03-25)

  * [#8] Chain optimizers' better behaviour:
    * execute only first successful optimizer by default (new options: `execute_only_first_png_optimizer` and `execute_only_first_jpeg_optimizer`)
    * ignore error when first optimizer fails, but second one succeeds
    * report an error when all optimizers fail (an error is ignored when `ignore_errors` is enabled)
    * BC break - `ChainOptimizer` constructor now requires 2nd parameter, and adds 3rd parameter logger:
```diff    
-    public function __construct(array $optimizers, $executeFirst = false)
+    public function __construct(array $optimizers, $executeFirst, LoggerInterface $logger)
```
