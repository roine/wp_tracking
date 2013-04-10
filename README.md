MIT License
===========

Copyright (c) 2013 Jonathan de Montalembert <[montalembert.jonathan@gmail.com]>

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the "Software"),
to deal in the Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, distribute, sublicense,
and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.

TODO
==

- add option to push manually
- add option to change the delta before pushing

What is it
==

A simple Wordpress plugin to help developer to keep track.
It saves some information about the visitors:
 - IP
 - Referer
 - Website Name
 - Current URL
 - Browser Language
 - Date of visit

How it works
==

At installation, a unique token is generated for the website and two tables. Everytime the website has a visitor, the information about him/her quoted above are saved in the local database using wpdb. Every 50 visits the data are sent via a post method.  The post look has two index, `json` and `uid`, the first one contains all the data in a json format, the second is the token linked to the website.