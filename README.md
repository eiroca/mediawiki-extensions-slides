# mediawiki-extensions-slides
## Usage
Build header and navigational links to transform a wiki page into a slide.

Create a wiki page for each slide of your presentation and in top of each page add the <slide></slide> marker.

## Syntax
Takes text between `<slides attribute="value"></slides>` tags, and splits it into individual lines. 
Each of the lines is either one of the following options, or treated as a topic (article/slide) name, or a sub-topic name (starting with '*'):

**Article reference**

Name1|Text in Navbar|Mouseover title

Name2|Text in Navbar

Name3

*Subarticle1|Text in Navbar|Mouseover    (belongs to last normal topic "Name3")

*Subarticle2|Text in Navbar

*Subarticle3


| Optional Attributes | Description |
| ------------------- | ----------- |
| _index_             | name of presentation (link\|display\|mouseover) |
| _prefix_            | prefix for the slides of the presentation (remove "name - " prefix from articles) |
| _hideMenu_          | hide the left menu column can be true or false (default: true, ) |
| _hideFooter_        | hide the footer can be true or false (default: true,) |
| _hideHeading_       | hide the first-level headline can be true or false (default: true) |
| _fontsize_          | the fontsize for the body, in % (default: 100%) |
| _showButtons_       | show \|< << >> >\| buttons on the navbar can be true or false (default: true) |
| _id_                | id of presentation slide to differentiate slides that belongs to multiple presentations (default: null) |
| _style_             | stile of the div |

### Example
```xml
<slides prefix="My Presentation" index="summary|Presentation summary">
Start
*Intro
*About
How to
Contact
</slides>
```

## Installation
To install copy Slides.php into the extensions/ directory of your mediawiki installation, and then add at the bottom of LocalSettings.php, but before the "? >", the following:

```php
require_once("extensions/Slides.php");
```

For full documentation please see: http://www.eiroca.net

