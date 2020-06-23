<?php
namespace Forkor;

trait introduct
{

    /*
     * Preparation process before rendering the Introduction page
     * @access public
     */
    public function introduct() {
        self::send_header( true );
        self::view_introduct();
    }

    /*
     * Render Introduction page
     * @access public
     */
    public function view_introduct() {
        $internal_styles = <<<EOS
#logo-image { float: left; }
#introduce-content { display: inline; position: relative; }
#introduce-content > *:first-child { text-indent: 0; }
#auto-indent-text { position: absolute; visibility: hidden; left: -9999px; top: -9999px; margin: 0; padding: 0; width: 50%; border: 0; line-height: 1rem; font-size: 1rem; }
#introduce-content p { margin-bottom: 0; padding-top: 0; padding-bottom: 0.25em; visibility: hidden; opacity: 0; transition: opacity 0.3s linear; }
#line-1 { margin-left: -1.0em; width: calc(100% + 1.0em); }
#line-2, #line-7 { margin-left: -0.3em; width: calc(100% + 0.3em); }
#line-3 { margin-left: 0.3em; width: calc(100% - 0.3em); }
#line-4, #line-5 { margin-left: 0.5em; width: calc(100% - 0.5em); }
#line-6 { margin-left: 0.2em; width: calc(100% - 0.2em); }
#line-8 { margin-left: -1.6em; width: calc(100% + 1.6em); }
#line-9 { margin-left: -3.2em; width: calc(100% + 3.2em); }
#line-10 { margin-left: -6.1em; width: calc(100% + 6.1em); }
#line-11 { margin-left: calc(-50% + 1em); width: calc(150% - 1em); line-height: 1.65; }
#floater { position: relative; display: inline-block; width: calc(150% - 2em); left: calc(-50% + 1em); }
#floater.shown { visibility: visible; opacity: 1; }
@media (min-width: 960px) {
  #introduce-content > *:first-child { text-indent: -1.5em; }
}
EOS;
        self::head( null, $internal_styles );
        $register_url = './' . REGISTER_PATH;
        $analyze_url  = './' . ANALYZE_PATH;
        $donation_url = 'https://ka2.org/';
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center mb2"><span class="forkor-logo"></span>Forkor</h1>
    <div class="flx-row item-start">
        <img src="./assets/forkor.svg" id="logo-image" class="w-1-3" alt="Forkor">
        <div id="introduce-content" data-switch-class="lg:w-2-3">
            <h2 id="h2-title" class="line-right txt-darkgray">What&#39;s the Forkor?</h2>
            <textarea id="auto-indent-text" disabled>Forkor is a service that provides a shortened URL feature that allows you to redirect any URL with a short URL.<br>
Shortened URL services are well known in the web world since they have been in use for a long time. Some of the most famous ones include "Google URL Shortener" and "Bit.ly" etc. However, the former service has already been discontinued and is not available to new users, while the latter is a paid service.<br>
Also the use of third party shortened URL services can have the following risks.<br>
<ul>
<li>It becomes unavailable due to the termination or closure of that service provided.</li>
<li>It becomes unusable or be restricted that service in violation of the Terms of Service.</li>
<li>It is unclear whether the redirect is a trustworthy URL, which could lead to a phishing site or a source of virus infection.</li>
</ul>
Therefore, you can avoid these risks by setting up your own shortened URL service using Forkor.<br>
You can use it permanently unless you terminate the service yourself, and there are no restrictions. Also, by using the same domain name as the host for the shortened URL, it is clear to all users that the redirected site is a URL registered and managed by that domain site, so there is no concern about the redirect destination.<br>
<br>
Best of all, Forkor is an open-source, so it&#39;s free to use forever.<br>
<br>
So, enjoy the best experience with Forkor.<br>
</textarea>
            <p id="line-1"></p>
            <p id="line-2"></p>
            <p id="line-3"></p>
            <p id="line-4"></p>
            <p id="line-5"></p>
            <p id="line-6"></p>
            <p id="line-7"></p>
            <p id="line-8"></p>
            <p id="line-9"></p>
            <p id="line-10"></p>
            <p id="line-11"></p>
            <p id="floater"></p>
        </div>
    </div>
    <hr class="dotted">
    <div class="">
        <h3 class="txt-darkgray">Make Original Shorten URL</h3>
        <p>
            Creating a shortened URL with the forkor is very easy. Forkor will automatically generate a shortened URL just by inputting the redirect URL on the registration page. Of course, you can customize it to your favorite URL.<br>
            You can also restrict access to the registration screen. Forkor does not have complicated processes such as user authentication so that the life cycle of the application can be rotated as simply as possible, but it is possible to restrict access to the registration screen by the remote address of the connection source.<br>
        </p>
        <div class="mxa my2 txt-center">
            <button type="button" id="btn-register" class="clr-prim" data-goto="{$register_url}">Let Get Started Soon!</button>
        </div>
    </div>
    <hr class="dotted">
    <div class="">
        <h3 class="txt-darkgray">See Stats Used Your Shorten URL</h3>
        <p>
            By default, Forkor comes bundled with an analysis mode that checks the statistics of the shortened URLs used. For example, on the analytics page, you can see information such as the referrer of the shortened URL and the number of redirects that were logged when the redirect was performed on the shortened Forkor URL. This may allow you to get web marketing tips from the access status of the shortened URL.<br>
            Of course, access to this analysis page can be restricted by the remote address of the connection source. If you forget to set these limits during installation, you can always update them by modifying the configuration file.<br>
        </p>
        <div class="mxa my2 txt-center">
            <button type="button" id="btn-analyze" data-goto="{$analyze_url}">Let Get Analyze!</button>
        </div>
    </div>
    <hr class="dotted">
    <div class="">
        <h3 class="txt-darkgray">Your contributions are welcome!</h3>
        <p>This application project is just the beginning. We would like to make it easier to use, improve it, and grow steadily.</p>
        <ul>
            <li>If there are any defects, malfunctions or any troubles, please let me know.</li>
            <li>If you have a desired function, please request it.</li>
            <li>Of course, making some donations will also help us.</li>
        </ul>
        <p>That is why we look for your cooperation.</p>
        <div class="mxa my2 txt-center">
            <button type="button" id="btn-donation" data-goto="{$donation_url}" class="clr-quat">Your Donation Grows Forkor</button>
        </div>
    </div>
</div>
EOD;
        echo $partial_main;
        $inline_scripts = <<<EOS
window.addEventListener('load',function(){
    // Inline Scripts
    const txtSize = (str, width, fs) => {
            let tlen  = document.createElement('span'),
                size  = { width: 0, height: 0 },
                fSize = fs || '1rem';
            
            tlen.style.display = width !== false ? 'inline-block' : 'inline';
            tlen.style.position = 'absolute';
            tlen.style.width = width !== false ? width + 'px' : 'auto';
            tlen.style.top = '-1000px';
            tlen.style.left = '-1000px';
            tlen.style.whiteSpace = width !== false ? 'normal' : 'nowrap';
            tlen.style.fontSize = fSize;
            //tlen.style.letterSpacing = '0.1rem';
            tlen.innerHTML = str;
            document.body.appendChild(tlen);
            size.width  = tlen.clientWidth;
            size.height = tlen.clientHeight;
            tlen.parentElement.removeChild(tlen);
            return size;
        };
    const pseudo = (id, css) => {
            id = id + '-pseudoStyle';
            let elm = document.getElementById(id);
            
            if ( css == undefined || css === '' ) {
                if ( elm != null ) {
                    elm.parentNode.removeChild(elm);
                }
                return;
            }
            if ( elm == null ) {
                styleTag = document.createElement('style');
                styleTag.id = id;
                styleTag.innerHTML = css;
                document.getElementsByTagName('head')[0].appendChild(styleTag);
            } else {
                elm.innerHTML = css;
            }
        };
    const autoIndentText = () => {
        let isSP  = /^Mozilla\/5.0\s\((iPhone;|iPad;|iPod;|Linux; U; Android|Linux; Android)/i.test(navigator.userAgent),
            scrW  = isSP ? window.screen.width : window.innerWidth,
            logo  = document.getElementById('logo-image'),
            icb   = document.getElementById('introduce-content'),
            title = document.getElementById('h2-title'),// icb.querySelector('h2'),
            ait   = document.getElementById('auto-indent-text'),
            prg   = ait.value.split("\\n"),
            rcp   = document.getElementById('floater'),
            splen = txtSize( '&nbsp;', false ).width + 2,
            rows  = 1,
            lines = [];
        
        // Initialize
        Array.prototype.forEach.call(document.querySelectorAll('[id^=line-]'), (elm) => {
            if ( scrW < 960 ) {
                elm.style.display = 'none';
                elm.style.visibility = 'hidden';
                elm.style.opacity = 0;
            } else {
                elm.style.display = 'block';
                elm.style.visibility = 'visible';
                elm.style.opacity = 1;
            }
            elm.innerHTML = '';
        });
        rcp.innerHTML = '';
        rcp.classList.remove('shown');
        pseudo('floatText', '');
        if ( scrW < 960 ) {
            let fH  = logo.clientHeight - title.clientHeight;
                //fHL = Math.ceil(fH / txtSize('1', false, '1rem').height);
            
            title.style.textIndent = '0';
            title.style.width = 'calc(100% - 0.5em)';
            
            pseudo('floatText', '#floater::before { content: " "; display: inline-block; float: left; width: calc(100% / 3); height: 1em; padding-bottom: '+ fH +'px; white-space: pre; }');
            rcp.innerHTML = prg.join('<br> ');
            rcp.classList.add('shown');
        } else {
            title.style.textIndent = '-1.5em';
            prg.forEach((str) => {
                let _ws   = str.split(' '),
                    _lstr = '',
                    _spc  = 0;
                
                _ws.forEach((_w, _i) => {
                    _lstr += _w + ' ';
                    _spc++;
                    if ( txtSize(_lstr, false).width > document.getElementById('line-' + rows).clientWidth - (_spc * splen) ) {
                        let re = new RegExp(_w + ' $'),
                            _line = _lstr.replace(re, '').trim();
                        lines.push(_line);
                        _lstr = _w + ' ';
                        _spc  = 1;
                        if ( rows < 11 ) {
                            rows++;
                        }
                    }
                }, lines);
                lines.push(_lstr.trim());
            });
            lines.forEach((line, idx) => {
                let num = idx < 11 ? idx + 1 : 11,
                    p   = document.getElementById('line-' + num);
                
                p.textContent += line;
            });
        }
    };

    // Binding the event handler of each buttons
    Array.prototype.forEach.call(document.querySelectorAll('[id^=btn-]'), (elm) => {
        elm.addEventListener('click', (evt) => {
            location.href = evt.target.dataset.goto;
        }, false);
    });

    // Binding resize event
    window.addEventListener( 'resize', autoIndentText, {passive: true}, false );
    
    // Binding orientationchange event
    window.addEventListener( 'orientationchange', autoIndentText, {passive: false}, false );
    
    autoIndentText();
    
}, false);
EOS;
        self::footer( true, $inline_scripts );
    }
}