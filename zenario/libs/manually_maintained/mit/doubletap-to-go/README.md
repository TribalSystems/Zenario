<a href="https://travis-ci.org/zenopopovici/DoubleTapToGo"><img src="https://travis-ci.org/zenopopovici/DoubleTapToGo.svg" alt="Build Status" /></a>

Double Tap to Go
================

Originally by: Osvaldas Valutis, www.osvaldas.info

Prevents first tap (click) on multi level menu children on mobile devices.  This allows a standard coded dropdown menu to open a second level of the menu on mobile devices.

When you tap the menu parent item for the first time, DoubleTapToGo prevents the browser from opening a new URL but allows that if tapped once again in succession.

## Example Markup
```
<nav id="nav">
	<ul>
		<li>
			<a href="#">Parent Menu Item 1</a>
				<ul>
					<li>
						<a href="#">Child Menu Item 1</a>
					</li>
					<li>
						<a href="#">Child Menu Item 2</a>
					</li>
					<li>
						<a href="#">Child Menu Item 3</a>
					</li>
				</ul>
		</li>
		<li>
			<a href="#">Parent Menu Item 2</a>
		</li>
	</ul>
</nav>
```
Note: The plugin should be only applied to the items that are parents – in order to avoid double-tap requirement on drop-down-less items.

### Enable plugin

```$( '#nav li:has(ul)' ).doubleTapToGo();```