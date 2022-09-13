jquery-doubletaptogo
============

Brings drop-down navigation tapping for touch devices. Built as jQuery Plugin.


[![Join the chat at https://gitter.im/dachcom-digital/jquery-doubletaptogo](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/dachcom-digital/jquery-doubletaptogo?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![npm](https://img.shields.io/npm/v/jquery-doubletaptogo.svg)](https://www.npmjs.com/package/jquery-doubletaptogo)
[![npm](https://img.shields.io/bower/v/jquery-doubletaptogo.svg)](https://www.npmjs.com/package/jquery-doubletaptogo)

Dependencies
============
- jQuery: http://jquery.com/

Installation
============

```html
<!-- 1. Create your drop-down navigation -->
<nav class="navigation">
    <ul>
        <li><a href="#">First level</a></li>
        <li><a href="#">First level</a>
            <ul>
                <li><a href="#">Second level</a></li>
                <li><a href="#">Second level</a></li>
            </ul>
        </li>
    </ul>
</nav>

<!-- 2. Include jQuery -->
<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

<!-- 3. Include plugin -->
<script src="dist/jquery.dcd.doubletaptogo.js" type="text/javascript"></script>

<!-- 4. Bind plugin to containers -->
<script type="text/javascript">
    $(function () {
        $('.navigation').doubleTapToGo();
    });
</script>
```

Options
============

- **automatic**: If set to true, tries to find out automatically which elements need doubletap and sets selector class on it. Set to false, if you have a more complex structure and set the selector class manually on the elements or specify a complex selector chain. `[Default: true]`
- **selectorClass**: Defines the selector class on which doubletap binds. `[Default: 'doubletap']`
- **selectorChain**: Defines the selector chain on which doubletap binds. `[Default: 'li:has(ul)']`

Changelog
============
3.0.0 Refactor to jQuery Plugin
-----------------
* removed dependency for jQuery Widget Factory
* Bugfixes

2.0.1 Bugfixes
-----------------
* added selector chain
* Bugfix for selector class in event listeners
* Bugfix for event listeners iOS / Android

2.0.0 Refactoring
-----------------
* added automatic mode
* added selector for binding
* removed levels option

1.0.0 Initial Release
---------------------
