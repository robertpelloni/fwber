<?php
/*
    Copyright 2025 FWBer.me

    This file is part of FWBer.

    FWBer is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    FWBer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero Public License for more details.

    You should have received a copy of the GNU Affero Public License
    along with FWBer.  If not, see <https://www.gnu.org/licenses/>.





    FWBer MVP Landing Page - Enhanced with signup tracking and modern mobile-first design

*/

require_once("_init.php");

// Force HTTPS
if (!isSecure()) {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

session_start();

// Redirect if already logged in
if (validateSessionOrCookiesReturnLoggedIn()) {
    require_once("_names.php");
    header('Location: '.getSiteURL().'/matches.php');
    exit();
}

// Track page views for analytics
if (!isset($_SESSION['page_tracked'])) {
    // Log page view (could integrate with Google Analytics, Mixpanel, etc.)
    error_log("Landing page view from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
    $_SESSION['page_tracked'] = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php /*
    <title><?php require_once("_names.php");echo getSiteName();?><?php require_once("_names.php");echo getTitleTagline();?></title>
    <?php include("head.php"); ?>
</head>
<body class="d-flex flex-column h-100">
    <?php include("h.php"); ?>

    <div class="position-relative overflow-hidden p-3 p-md-5 py-5 mr-md-3 mt-md-5 ml-md-3 mb-md-3 text-center bg-light" style="margin-top: 3.5rem !important;">
        <div class="col-md-5 p-lg-5 mx-auto my-5">
            <h1 class="display-4 font-weight-normal"><img src="/images/fwber_logo_icon.png" alt="fwber" id="fwber_logo_img" align="middle" width="180px" style="vertical-align:bottom; margin-bottom:5px; margin-right:-4px;"/> is a <b>completely new</b> kind of Adult Match site.</h1>
            <p class="lead font-weight-normal">
                <br>
                Finally, a better replacement<br>
                for Craigslist and AdultFriendFinder!<br>
                <br>
                No fees, <b>totally free</b> forever.<br>
                No bots, spam, ads, or popups.<br>
            </p>
            <a class="btn btn-outline-secondary" href="/#joinNow">Join Now</a>
        </div>
    </div>

    <div class="d-md-flex flex-md-equal w-100 my-md-3 pl-md-3">
        <div class="site-header mr-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 p-3">
                <h2 class="display-5">Supports all types of lifestyles and preferences.</h2>
                <p class="lead">Men, women, couples, groups, transgender, crossdressers, straight, gay, bisexual.</p>
                <i class='fas fa-mars' style='font-size:48px;color:deepskyblue'></i>
                <i class='fas fa-venus' style='font-size:48px;color:deeppink'></i>
                <i class='fas fa-venus-mars' style='font-size:48px;color:green'></i>
                <i class='fas fa-transgender' style='font-size:48px;color:mediumpurple'></i>
                <i class='fas fa-transgender-alt' style='font-size:48px;color:rebeccapurple'></i>
                <i class='fas fa-mars-double' style='font-size:48px;color:deepskyblue'></i>
                <i class='fas fa-venus-double' style='font-size:48px;color:deeppink'></i>
            </div>

        </div>
        <div class="bg-light mr-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 py-3">
                <h2 class="display-5">Match by dozens of specific sexual interests and fetishes.</h2>
                <p class="lead">Find exactly who you are looking for, no matter how kinky.</p>
            </div>
        </div>
    </div>

    <div class="d-md-flex flex-md-equal w-100 my-md-3 pl-md-3">
        <div class="bg-light mr-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 py-3">
                <h2 class="display-5">You don't need a public picture. We make one for you.</h2>
                <p class="lead">Create your <?php echo getSiteName();?> avatar by filling out your profile.</p>
                <img src="/images/avatars.png" border="0" style="height:100px; vertical-align:middle;"/>
            </div>

        </div>
        <div class="site-header mr-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 p-3">
                <h2 class="display-5">No searching. Automatic matches based on sexual interests.</h2>
                <p class="lead">No digging through hundreds of profiles. We find you and send you to each other.</p>
            </div>

        </div>
    </div>

    <div class="d-md-flex flex-md-equal w-100 my-md-3 pl-md-3">
        <div class="site-header mr-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 p-3">
                <h2 class="display-5">Privacy comes first. We only show your profile to your matches.</h2>
                <p class="lead">Your pictures always remain private until you agree to show them.</p>
            </div>

        </div>
        <div class="bg-light mr-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 py-3">
                <h2 class="display-5"><a href="https://github.com/fwber-code/fwber">Open source</a> website code.</h2>
                <p class="lead">For the people, by the people! Anyone can verify your data is stored securely and your secrets are safe. The future belongs to us!</p>
            </div>

        </div>
    </div>


    <br>
    <br>
    <br>

    <div class="text-center" id="joinNow">

        <form class="form-signin" action="_makeAccount" method="POST" enctype="multipart/form-data"
              name="joinFormName" id="joinFormID">
            <fieldset style="text-align:left;">

                <h1 class="h3 mb-3 font-weight-normal text-center"> Join Now</h1>

                <label for="idEmail" class="sr-only">Email address</label>
                <input type="email" id="idEmail" class="form-control required" placeholder="Email address" name="nameEmail" required>

                <br>
                <label for="idPassword" class="sr-only">Password</label>
                <input type="password" id="idPassword" class="form-control required" placeholder="Password" name="namePassword" required>

                <label for="idVerify" class="sr-only">Verify Password</label>
                <input type="password" id="idVerify" class="form-control required" placeholder="Verify Password" name="nameVerify" required>

                <div class="text-center">
                    <label class="section" for="captchaDiv"></label>
                    <div id="captchaDiv">
                        <div class="g-recaptcha" data-sitekey="6LfUldISAAAAAJjP3rj8cCd1CEmBrfdEMVE_51eZ"
                             data-callback="reCaptchaCallback"></div>
                    </div>
                    <input type="hidden" class="hiddenRecaptcha required" name="hiddenRecaptcha" id="hiddenRecaptcha">
                    <br>
                    <div>
                        <label class="checkbox text-left">
                            <input type="checkbox" onclick="toggle(this)" name="nameAgreeLegalAge" class="required" required> I certify that I am of legal adult age in my country
                        </label>
                    </div>
                    <br>
                    <div>
                        <label class="checkbox text-left">
                            <input type="checkbox" onclick="toggle(this)" name="nameAgreeTOS" class="required" required> I agree to <?php echo getSiteName();?> <a href="/tos" target="_blank">Terms Of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <br>
                    <br>
                    <br>
                    <br>
                    <button class="btn btn-lg btn-primary" type="submit" name="createAccountButtonName"
                            id="createAccountButtonID">Join
                    </button>
                </div>
                <br>

                <div class="smallText">
                    <span style="color:#ff00a8; font-size:10pt; font-weight:normal;">You can completely delete your profile at any time.</span><br>
                </div>
                <br>
                <br>
            </fieldset>
        </form>
    </div>

    <?php include("f.php");?>

    <script src="/js/jquery-3.4.1.min.js"></script>
    <script src="/bootstrap-4.3.1-dist/js/bootstrap.bundle.min.js"></script>

    <script src="/js/jquery-validation-1.19.1/dist/jquery.validate.min.js" type="text/javascript"></script>

    <script src='https://kit.fontawesome.com/a076d05399.js'></script>

    <script type="text/javascript">

        var doSubmit = false;

        $.validator.setDefaults({});
        $().ready
        (
            function () {
                // validate the comment form when it is submitted
                $("#joinFormID").validate
                ({
                    errorElement: "div",
                    ignore: ".ignore", //needed for hiddenCaptcha input to validate captcha
                    rules:
                        {
                            nameEmail:
                                {
                                    email: true
                                },
                            namePassword:
                                {
                                    required: true,
                                    minlength: 5
                                },
                            nameVerify:
                                {
                                    equalTo: "#idPassword"
                                },
                            hiddenRecaptcha:
                                {
                                    required: function () {
                                        if (doSubmit == false) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                        }
                    ,
                    messages:
                        {
                            namePassword:
                                {
                                    required: "Enter a password.",
                                    minlength: "Your password must be at least 5 characters long"
                                },
                            nameEmail:
                                {
                                    required: "We need your email address to send you matches."
                                },
                            nameVerify:
                                {
                                    required: "Verify your password."
                                },
                            nameAgreeLegalAge: "You must be of legal age.",
                            nameAgreeTOS: "You must agree to the Terms Of Service and Privacy Policy to join.",
                            hiddenRecaptcha: "Please complete the captcha to continue."
                        }
                    ,
                });
*/ ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php require_once("_names.php");echo getSiteName();?> - Free Adult Dating & Hookups</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FWBer">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- iOS PWA Meta Tags -->
    <meta name="apple-mobile-web-app-title" content="FWBer">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Windows 8/10 Tile -->
    <meta name="msapplication-TileColor" content="#6366f1">
    <meta name="msapplication-TileImage" content="/mstile-150x150.png">
    
    <!-- Modern CSS Framework -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#ec4899'
                    }
                }

            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        .hover-scale {
            transition: transform 0.2s ease-in-out;
        }
        .hover-scale:hover {
            transform: scale(1.05);
        }
    </style>

    <!-- Analytics -->
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
        
        // Track signup funnel
        function trackEvent(action, label) {
            gtag('event', action, {
                'event_category': 'signup_funnel',
                'event_label': label
            });
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Hero Section -->
    <div class="gradient-bg min-h-screen flex items-center justify-center px-4">
        <div class="max-w-4xl mx-auto text-center text-white">
            <div class="mb-8">
                <img src="/images/fwber_logo_icon.png" alt="FWBer" class="w-24 h-24 mx-auto mb-4 rounded-full shadow-lg">
                <h1 class="text-4xl md:text-6xl font-bold mb-4">
                    The Future of Adult Dating
                </h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90">
                    AI-Generated Avatars • Location-Based Matching • Complete Privacy
                </p>
            </div>
            
            <!-- Key Features -->
            <div class="grid md:grid-cols-3 gap-6 mb-12">
                <div class="glass-effect rounded-lg p-6 text-gray-800">
                    <i class="fas fa-robot text-3xl text-primary mb-4"></i>
                    <h3 class="text-lg font-semibold mb-2">AI-Generated Avatars</h3>
                    <p class="text-sm">Custom avatars based on your physical attributes</p>
                </div>
                <div class="glass-effect rounded-lg p-6 text-gray-800">
                    <i class="fas fa-map-marker-alt text-3xl text-secondary mb-4"></i>
                    <h3 class="text-lg font-semibold mb-2">Live Location</h3>
                    <p class="text-sm">Find matches at festivals, bars, and events</p>
                </div>
                <div class="glass-effect rounded-lg p-6 text-gray-800">
                    <i class="fas fa-shield-alt text-3xl text-green-500 mb-4"></i>
                    <h3 class="text-lg font-semibold mb-2">100% Private</h3>
                    <p class="text-sm">Photos stay private until you choose to share</p>
                </div>
            </div>
            
            <!-- CTA Button -->
            <button onclick="scrollToSignup()" class="bg-white text-primary px-8 py-4 rounded-full font-bold text-lg hover-scale shadow-lg">
                Join Free Now
            </button>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-20 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-gray-800">
                Built for Every Lifestyle
            </h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div class="p-6">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-mars text-blue-500"></i>
                        <i class="fas fa-venus text-pink-500"></i>
                    </div>
                    <h3 class="font-semibold mb-2">All Orientations</h3>
                    <p class="text-gray-600 text-sm">Straight, gay, bi, pan - everyone welcome</p>
                </div>
                
                <div class="p-6">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-users text-purple-500"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Couples & Groups</h3>
                    <p class="text-gray-600 text-sm">Find other couples or join groups</p>
                </div>
                
                <div class="p-6">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-heart text-red-500"></i>
                    </div>
                    <h3 class="font-semibold mb-2">STI-Positive Friendly</h3>
                    <p class="text-gray-600 text-sm">Safe matching for all health statuses</p>
                </div>
                
                <div class="p-6">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-mask text-indigo-500"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Kink & Fetish</h3>
                    <p class="text-gray-600 text-sm">Match on dozens of specific interests</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Signup Section -->
    <div id="signup" class="py-20 px-4 bg-gray-50">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <h2 class="text-2xl font-bold text-center mb-8 text-gray-800">Join FWBer Free</h2>
                
                <form id="signupForm" action="_makeAccount.php" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="nameEmail" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="your@email.com">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" id="password" name="namePassword" required minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Minimum 8 characters">
                    </div>
                    
                    <div>
                        <label for="passwordVerify" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <input type="password" id="passwordVerify" name="nameVerify" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Confirm your password">
                    </div>

                    <!-- reCAPTCHA -->
                    <div class="flex justify-center">
                        <?php $recaptchaSite = $_ENV['RECAPTCHA_SITE_KEY'] ?? ''; ?>
                        <?php if ($recaptchaSite): ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSite, ENT_QUOTES, 'UTF-8'); ?>" data-callback="reCaptchaCallback"></div>
                        <?php else: ?>
                        <div class="text-sm text-gray-500">reCAPTCHA is not configured.</div>
                        <?php endif; ?>
                        <input type="hidden" name="hiddenRecaptcha" id="hiddenRecaptcha">
                    </div>

                    <!-- Legal Checkboxes -->
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="nameAgreeLegalAge" required class="mr-2 rounded">
                            <span class="text-sm text-gray-700">I am 18+ years old</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="nameAgreeTOS" required class="mr-2 rounded">
                            <span class="text-sm text-gray-700">I agree to the <a href="/tos" class="text-primary hover:underline" target="_blank">Terms of Service</a> and <a href="/privacy" class="text-primary hover:underline" target="_blank">Privacy Policy</a></span>
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full bg-primary text-white py-3 px-4 rounded-md font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors"
                            onclick="trackEvent('signup_attempt', 'form_submission')">
                        Create My Account
                    </button>
                </form>
                
                <p class="text-center text-xs text-gray-500 mt-6">
                    <i class="fas fa-trash-alt mr-1"></i>
                    You can delete your account anytime
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12 px-4">
        <div class="max-w-6xl mx-auto text-center">
            <p class="mb-4">FWBer - Free, Open Source Adult Dating</p>
            <div class="flex justify-center space-x-6">
                <a href="/privacy" class="hover:underline">Privacy</a>
                <a href="/tos" class="hover:underline">Terms</a>
                <a href="/contact" class="hover:underline">Contact</a>
                <a href="https://github.com/fwber-code/fwber" class="hover:underline">
                    <i class="fab fa-github mr-1"></i>Open Source
                </a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://www.google.com/recaptcha/api.js" defer></script>
    <script>
        // Smooth scrolling
        function scrollToSignup() {
            document.getElementById('signup').scrollIntoView({ 
                behavior: 'smooth' 
            });
            trackEvent('cta_click', 'hero_button');
        }

        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordVerify = document.getElementById('passwordVerify').value;
            
            if (password !== passwordVerify) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            trackEvent('signup_submit', 'form_valid');
        });

        // reCAPTCHA callback
        let doSubmit = false;
        function reCaptchaCallback(response) {
            if (response) {
                doSubmit = true;
                document.getElementById('hiddenRecaptcha').value = response;
            }
        }

        // Track user engagement
        let scrollDepth = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            const documentHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const currentDepth = Math.round((currentScroll / documentHeight) * 100);
            
            if (currentDepth > scrollDepth && currentDepth % 25 === 0) {
                scrollDepth = currentDepth;
                trackEvent('scroll_depth', `${currentDepth}%`);
            }
        });

        // PWA Installation
        let deferredPrompt;
        const installButton = document.createElement('button');
        installButton.innerHTML = '<i class="fas fa-download mr-2"></i>Install App';
        installButton.className = 'fixed bottom-4 right-4 bg-primary text-white px-4 py-2 rounded-full shadow-lg hover:bg-indigo-700 transition-all transform hover:scale-105 z-50 hidden';
        installButton.id = 'installPWA';
        document.body.appendChild(installButton);

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installButton.classList.remove('hidden');
        });

        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    trackEvent('pwa_install', 'user_accepted');
                }
                deferredPrompt = null;
                installButton.classList.add('hidden');
            }
        });

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then((registration) => {
                        console.log('SW registered: ', registration);
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // Request notification permission for match alerts
        if ('Notification' in window && 'serviceWorker' in navigator) {
            document.getElementById('signupForm').addEventListener('submit', async (e) => {
                if (Notification.permission === 'default') {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        trackEvent('notification_permission', 'granted');
                    }
                }
            });
        }
    </script>
</body>
</html>
