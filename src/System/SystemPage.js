/*
 * Copyright © 2009-2017, Michael Steenbeek
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

'use strict';

$('#testColors').on('click', function ()
{
    let body = document.getElementsByTagName('body');
    let bodyset = document.getElementsByName('backgroundColor');
    body[0].style.backgroundColor = bodyset[0].value;
    let menu = document.getElementsByClassName('menu');
    let menuset = document.getElementsByName('menuColor');
    let menubg = document.getElementsByName('menuBackground');
    menu[0].style.backgroundColor = menuset[0].value;
    menu[0].style.backgroundImage = "url('" + menubg[0].value + "')";
    let article = document.getElementsByClassName('inhoud');
    let articleset = document.getElementsByName('articleColor');
    article[0].style.backgroundColor = articleset[0].value;
});
