# Magic image resize

| copyright | Copyright 2020-2021 (C) computer.daten.netze::feenders. All rights reserved. |
| --- | --- |
| license | GNU/GPL v3, see LICENSE.txt |

Resize content images automatically if tagged with a data-resize attribute.

```html
<img src="images/myLargeImg.jpg" class="img-fluid" width="250" height="128" data-resize="crop" />
```
This will crop a image to exactly 250 x 128 pixels and put it into images/.thumbs/250-128/myLargeImg.jpg. The data-resize attribute does the trick. The html Image src attribute will be replaced with the urls to the scaled version in the .thumbs - folder.

Images can be scaled, cropped or filled

* data-resize="scale" - Scale the Image and keep aspect ratio. (default value)
* data-resize="crop" - Crop the and center the image to the exact size. Ignore aspect.
* data-resize="fit" - Fit image to size and fill the rest with background color.

If no special width or height attributes are provided MIResize uses the plugin default width and height. You can change default resize mode and dimensions by editing the plugin options. The options provide also a mechanism to clear the thumbs cache. Use the clear-tumbs function with care!

Only the image source is replaced. This means can still use responsive classes  like img-fluid or img-responsive.

The plugin will be activated automatically during installation.

For help visit
https://www.feenders.de/magic-image-resize.html
