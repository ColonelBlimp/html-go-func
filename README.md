# HTML-go
HTML-go is a databaseless, flat-file blogging platform, which is very flexible, simple and fast. Its
nearest competitor is htmly platform.

# Landing Pages
HTML-go has four editable landing pages for **blog**, **category** and **tag**. The fourth
landing page is the **index** or **home** page and at the root of all other pages.
All of these pages are listed in the `slugIndex` and the `pageIndex` and are considered
to be pages by the system.
### Home Page (main index)
The data file is located at `content/common/pages/index.md` and is
listed in two indexes: `slugIndex` and `pageIndex` under the key `/`.
### Category Index Page
The data file is located at `content/common/landing/category/index.md` and
is listed in one index: `slugIndex` under the key `category`.
### Blog Index Page
The data file is located at `content/common/landing/posts/index.md` and is
lised in one index: `slugIndex` under the key `blog`. Generally,
this index page is use if the 'blog' link is enabled it will point to this page.
### Tag Index Page
This data file is located at `content/common/landing/tags/index.md` and
is listed in one index: `slugIndex` under the key `tag`.

# Routing
There is no complex router for html-go. Rather, html-go uses a indexing system
whereby all the content is indexed and the URI used as the index key. Apart from
a few special cases such as landing pages, the requested URI is passed to the
indexing system to check if it exists, if it does it is loaded and rendered.
Otherwise the *not found* page is rendered.

# Content
Content files are in JSON format as JSON is handled natively by PHP and conversion
between JSON object, `stdClass` and an `array` is also handled natively.
The minimum required for a valid content file is:

    {
        "title": "some title",
        "description": "some description",
        "body": "The content."
    }

### Menus
Menus entries are valid for *pages* only. A single content page can be listed in as many menus
as required. Defined menus are available on the `content.menus.[menu_name]` object
within the template context.

For example, below is a sample home page with the page listed in two menus:
**main** and **footer**. The **name** for the menu link is *Home* in both menus
and the position (weight) is the first entry. The actual link will be the same
for both menus and is defined by the system, in this case `/`

    {
        "title" : "Our Website",
        "description" : "Welcome to our website",
        "menus": {
            "main": {
                "name": "Home",
                "weight": 1
            },
            "footer": {
                "name": "Home",
                "weight": 1
            }
        },
        "body" : "Welcome to our new website."
    }

The above menus can be accessed by the following **Twig** code:

    {{ content.menus.main }}

and

    {{ content.menus.footer }}

#### Twig Code Sample
    {% if content.menus.main is defined %}
    {% for main in content.menus.main %}
            <a href="{{ content.site.url }}{%if main.key starts with '/'%}{{ main.key }}{% else %}/{{ main.key }}{% endif %}">{{ main.name }}</a>
    {% endfor %}
    {% endif %}

# Templating

### Context Variables

|Twig |Smarty |PHP |Config | Comments|
|--- | --- | --- | --- | ---|
|`{{ site.language }}`|?|?|site.language | Default is "en"|
