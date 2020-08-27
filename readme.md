# Magic image resize

| copyright | Copyright 2020 (C) computer.daten.netze::feenders. All rights reserved. |
| --- | --- |
| license | GNU/GPL v3, see LICENSE.txt |

Resize content images automatically if tagged with a data-resize attribute 

```html
<img src="images/myLargeImg.jpg" class="img-fluid" width="250" height="128" data-resize="crop" />
```
Crop image to exactly 250 x 128 and put it into images/.thumbs/250-128/myLargeImg.jpg

Image src will be replaced with a scaled version in a .thumbs - folder.

Images can be scaled, cropped or filled. 

For Help visit https://www.feenders.de
