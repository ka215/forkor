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
.popup-image { cursor: pointer; }
.border-rounded { border: solid 1px #ddd; border-radius: 0.5em; }
/* icons */
[class^=icon-] { position: relative; display: inline-block; width: 1em; height: 1em; font-size: 1em; line-height: 1; margin: auto 1em auto 0; }
[class^=icon-]::after { position: absolute; left: 0; top: 0; }
.icon-register::after { content: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' width='16px' height='16px' viewBox='0 0 24 24'><path fill='%23ffffff' d='M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z'></path></svg>"); }
.icon-analyze::after { content: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' width='24px' height='24px' viewBox='0 0 24 24'><path fill='%23594e52' d='M16 1H2c-.55 0-1 .45-1 1v14c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V2c0-.55-.45-1-1-1zM7 13H5V8h2v5zm3 0H8V5h2v8zm3 0h-2V9h2v4z'></path></svg>"); }
.icon-heart::after { content: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' width='16px' height='16px' viewBox='0 0 24 24'><path fill='%23ffffff' d='M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55l-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z'></path></svg>"); }
EOS;
        self::head( null, $internal_styles );
        $register_url = './' . REGISTER_PATH;
        $analyze_url  = './' . ANALYZE_PATH;
        $donation_url = 'https://github.com/sponsors/ka215';
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center mb2"><span class="forkor-logo"></span>Forkor</h1>
    <div class="mb2">
        <h2 class="line-right txt-darkgray">What&#39;s the Forkor?</h2>
        <img src="./assets/forkor.svg" class="fr" data-switch-class="sm:w-2-5,md:w-1-3,lg:w-1-4" alt="Forkor">
        <p>
            Forkor is a service that provides a shortened URL feature that allows you to redirect any URL with a short URL.<br>
            Shortened URL services are well known in the web world since they have been in use for a long time. Some of the most famous ones include "Google URL Shortener" and "Bit.ly" etc. However, the former service has already been discontinued and is not available to new users, while the latter is a paid service.<br>
            Also the use of third party shortened URL services can have the following risks.<br>
        </p>
        <ul>
            <li>It becomes unavailable due to the termination or closure of that service provided.</li>
            <li>It becomes unusable or be restricted that service in violation of the Terms of Service.</li>
            <li>It is unclear whether the redirect is a trustworthy URL, which could lead to a phishing site or a source of virus infection.</li>
        </ul>
        <p>
            Therefore, you can avoid these risks by setting up your own shortened URL service using Forkor.<br>
            You can use it permanently unless you terminate the service yourself, and there are no restrictions. Also, by using the same domain name as the host for the shortened URL, it is clear to all users that the redirected site is a URL registered and managed by that domain site, so there is no concern about the redirect destination.<br>
            <br>
            Best of all, Forkor is an open-source, so it&#39;s free to use forever.<br>
            <br>
            So, enjoy the best experience with Forkor.<br>
        </p>
        <div class="clearfix"></div>
    </div>
    <div class="my2">
        <h3 class="line-right txt-darkgray">Make Original Shortened URL</h3>
        <img src="./assets/forkor_register_page.png" class="popup-image border-rounded fl" data-switch-class="sm:w-2-5,md:w-1-3,lg:w-1-3" alt="Forkor Register Page">
        <p>
            Creating a shortened URL with the forkor is very easy. Forkor will automatically generate a shortened URL just by inputting the redirect URL on the registration page. Of course, you can customize it to your favorite URL.<br>
            You can also restrict access to the registration screen. Forkor does not have complicated processes such as user authentication so that the life cycle of the application can be rotated as simply as possible, but it is possible to restrict access to the registration screen by the remote address of the connection source.<br>
        </p>
        <div class="mxa my2 txt-center">
            <button type="button" id="btn-register" class="clr-prim" data-goto="{$register_url}"><i class="icon-register"></i>Let Get Started Soon!</button>
        </div>
        <br class="clearfix">
    </div>
    <div class="my2">
        <h3 class="line-right txt-darkgray">See Stats Used Shortened URL</h3>
        <img src="./assets/forkor_analyze_page.png" class="popup-image border-rounded fr" data-switch-class="sm:w-2-5,md:w-1-3,lg:w-1-3" alt="Forkor Analyze Page">
        <p>
            By default, Forkor comes bundled with an analysis mode that checks the statistics of the shortened URLs used. For example, on the analytics page, you can see information such as the referrer of the shortened URL and the number of redirects that were logged when the redirect was performed on the shortened Forkor URL. This may allow you to get web marketing tips from the access status of the shortened URL.<br>
            Of course, access to this analysis page can be restricted by the remote address of the connection source. If you forget to set these limits during installation, you can always update them by modifying the configuration file.<br>
        </p>
        <div class="mxa my2 txt-center">
            <button type="button" id="btn-analyze" data-goto="{$analyze_url}"><i class="icon-analyze"></i>Let Get Analyze!</button>
        </div>
        <br class="clearfix">
    </div>
    <div class="my2">
        <h3 class="line-right txt-darkgray">Your contributions are welcome!</h3>
        <p>This application project is just the beginning. We would like to make it easier to use, improve it, and grow steadily.</p>
        <ul>
            <li>If there are any defects, malfunctions or any troubles, please let us know.</li>
            <li>If you have a desired function, please request it.</li>
            <li>Of course, making some donations will also help us.</li>
        </ul>
        <p>That is why we look for your cooperation.</p>
        <div class="mxa my2 txt-center">
            <button type="button" id="btn-donation" data-goto="{$donation_url}" class="clr-quat"><i class="icon-heart"></i>Your Donation Grows Forkor</button>
        </div>
    </div>
</div>
EOD;
        echo $partial_main;
        $inline_scripts = <<<EOS
window.addEventListener('load',function(){
    // Binding the event handler to popup image
    Array.prototype.forEach.call(document.querySelectorAll('.popup-image'), function(elm) {
        elm.addEventListener('click', function(evt) {
            var imgContainer = document.createElement('div'),
                previewImage = document.createElement('img');
            previewImage.src = elm.src;
            previewImage.style.width = '100%';
            imgContainer.appendChild(previewImage);
            imgContainer.style.width = '100%';
            showDialog(null, imgContainer, 'dismiss-outside');
        }, false);
    });

    // Binding the event handler of each buttons
    Array.prototype.forEach.call(document.querySelectorAll('[id^=btn-]'), function(elm) {
        elm.addEventListener('click', function(evt) {
            location.href = evt.target.dataset.goto;
        }, false);
    });

}, false);
EOS;
        self::footer( true, $inline_scripts );
    }
}
