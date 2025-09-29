@extends('frontend.layouts.app')

@section('frontent-content')

<!-- Hero Section -->

<section class="hero-section d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8" data-aos="fade-up">
                <div class="badge-pill mb-3">
                    <i class="bi bi-wifi me-1"></i>Stay connected across the globe
                </div>
                <h1 class="display-5 fw-bold mb-3">
                    Smart connectivity for the <span class="highlight">digital nomad</span>
                </h1>
                <p class="lead mb-4">Experience seamless internet connectivity worldwide with our premium eSIM solutions for international travelers — instant setup, reliable coverage!</p>
                <!-- Plan cards stack -->
                <div class="plan-stack d-flex justify-content-center position-relative mb-4" data-aos="fade-up"
                    data-aos-delay="100">
                    @foreach($esimPackages as $index => $package)
                    <div class="plan-card {{$index == 1 ? 'active' : ''}}">
                        <h5 class="mb-1">{{$package->data}}</h5>
                        <p class="small mb-1">{{$package->day}} days</p>
                        <ul class="list-unstyled small mb-3">
                            <li>{{$package->name}}</li>
                            <li>Use your favourite apps</li>
                        </ul>
                        <button data-bs-toggle="modal" data-bs-target="#downloadAppModal" class="btn btn-outline-primary btn-sm">From ${{$package->net_price}}</button>
                    </div>
                    @endforeach
                </div>
                <!-- Trust rating -->
                <div class="rating-info d-flex align-items-center justify-content-center" data-aos="fade-up"
                    data-aos-delay="200">
                    <div class="me-2">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-half"></i>
                    </div>
                    <span class="fw-bold">4.9/5</span>
                    <span class="ms-2 small">(62K+ satisfied customers globally)</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Intro / About Section -->
<section class="intro-section py-5" id="about">
    <div class="container" data-aos="fade-up">
        <p class="text-center intro-text mx-auto">In today's interconnected world, staying online is <span
                class="highlight">essential</span>. We deliver <span class="highlight">dependable,
                cost-effective, and simple</span> connectivity solutions. Travel anywhere without the stress of
            expensive roaming fees or connection issues.</p>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5" id="plans">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="badge-pill">Discover the smartest way to stay online</div>
            <h2 class="fw-bold mt-2">Experience dependable and <span class="highlight">budget-friendly</span> connectivity on
                your journeys</h2>
            <p class="mb-0 lead">Our service leverages your device's eSIM technology to connect with local carriers, eliminating the need for physical SIM cards or additional devices.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="0">
                <div class="feature-box p-4 h-100 bg-feature-1">
                    <div class="feature-icon"><i class="bi bi-infinity"></i></div>
                    <h5 class="fw-semibold mb-2">Flexible data plans</h5>
                    <p class="small mb-0">Access flexible data packages tailored for various destinations with
                        peace of mind.</p>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-box p-4 h-100 bg-feature-2">
                    <div class="feature-icon"><i class="bi bi-app"></i></div>
                    <h5 class="fw-semibold mb-2">Access all your apps</h5>
                    <p class="small mb-0">Navigate safely, discover amazing dining spots, and explore more - all
                        while maintaining connectivity.</p>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-box p-4 h-100 bg-feature-3">
                    <div class="feature-icon"><i class="bi bi-headset"></i></div>
                    <h5 class="fw-semibold mb-2">Round-the-clock support</h5>
                    <p class="small mb-0">Our dedicated support team is available around the clock whenever assistance is needed.
                    </p>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-box p-4 h-100 bg-feature-4">
                    <div class="feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h5 class="fw-semibold mb-2">High-speed connectivity</h5>
                    <p class="small mb-0">Establish instant connections upon arrival and enjoy rapid mobile data
                        speeds.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Esimtel Section -->
<section class="why-section py-5" id="why">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="badge-pill">Why choose us</div>
            <h2 class="section-title mt-2">Why choose <span class="highlight">Esimetry</span>?</h2>
            <p class="why-text mb-0">We go beyond just providing data. Discover what makes Esimetry the perfect
                travel companion for your next adventure.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="why-card bg-why-1 h-100">
                    <div class="why-icon" style="background: rgba(0,102,255,0.1);"><i class="bi bi-globe2"></i>
                    </div>
                    <h5 class="fw-semibold mb-2">Worldwide coverage</h5>
                    <p class="small mb-0">Stay connected in 200+ countries and regions with one simple eSIM.
                        Wherever your journey takes you, we're there too.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="why-card bg-why-2 h-100">
                    <div class="why-icon" style="background: rgba(0,102,255,0.1);"><i
                            class="bi bi-cash-coin"></i></div>
                    <h5 class="fw-semibold mb-2">Affordable plans</h5>
                    <p class="small mb-0">Transparent pricing with no hidden fees. Choose a data plan that suits
                        your budget and needs.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="why-card bg-why-3 h-100">
                    <div class="why-icon" style="background: rgba(0,102,255,0.1);"><i
                            class="bi bi-lightning"></i></div>
                    <h5 class="fw-semibold mb-2">Instant activation</h5>
                    <p class="small mb-0">Activate your eSIM instantly via email or the app. Get online in
                        minutes and avoid long queues at physical stores.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="why-card bg-why-4 h-100">
                    <div class="why-icon" style="background: rgba(0,102,255,0.1);"><i
                            class="bi bi-shield-check"></i></div>
                    <h5 class="fw-semibold mb-2">Secure & reliable</h5>
                    <p class="small mb-0">Enjoy secure connections with trusted carriers. Our network ensures
                        your data and privacy stay protected.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it works Section -->
<section class="works-section py-5" id="how-it-works">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-12" data-aos="fade-up">
                <div class="badge-pill mb-2">We kept it simple for you</div>
                <h2 class="fw-bold mb-3">How <span class="highlight">Esimetry</span> eSIM works?</h2>
                <p class="mb-4">This is everything you love about your regular mobile network, connecting you
                    when you travel.</p>
                <div class="step mb-3 d-flex align-items-start">
                    <div class="step-num me-3">1</div>
                    <div>
                        <h6 class="fw-semibold mb-1">Download and install the Esimetry app</h6>
                        <p class="small mb-0">Get our app on your device before you leave. It’s available on the
                            App Store and Google Play.</p>
                    </div>
                </div>
                <div class="step mb-3 d-flex align-items-start">
                    <div class="step-num me-3">2</div>
                    <div>
                        <h6 class="fw-semibold mb-1">Activate your eSIM and purchase data plan that suits you
                        </h6>
                        <p class="small mb-0">Activate instantly by scanning the QR code we send to your email,
                            then choose a plan based on your destination.</p>
                    </div>
                </div>
                <div class="step d-flex align-items-start">
                    <div class="step-num me-3">3</div>
                    <div>
                        <h6 class="fw-semibold mb-1">Use your data plan as soon as you arrive</h6>
                        <p class="small mb-0">Use your data as soon as you arrive at your destination and enjoy
                            your trip.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- FAQ Section -->
<section class="faq-section py-5" id="faq">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="badge-pill">Have questions?</div>
            <h2 class="section-title mt-2">Frequently Asked <span class="highlight">Questions</span></h2>
            <p class="why-text mb-0">Here are answers to some common questions about how Esimetry works. If you
                need more help, our support team is just a message away.</p>
        </div>
        <div class="accordion" id="faqAccordion" data-aos="fade-up" data-aos-delay="100">
            @foreach($faqs as $faq)
            <div class="accordion-item">
                <h2 class="accordion-header" id="{{$faq->id}}-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#faqOne{{$faq->id}}" aria-expanded="false" aria-controls="faqOne">
                        {{$faq->question}}
                    </button>
                </h2>
                <div id="faqOne{{$faq->id}}" class="accordion-collapse collapse" aria-labelledby="{{$faq->id}}-heading"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        {{$faq->answer}}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Download Section -->
<section class="download-section py-5" id="download">
    <div class="container">
        <div class="row justify-content-center text-center" data-aos="fade-up">
            <div class="col-lg-9">
                <div class="badge-pill mb-3">Stay connected across the globe</div>
                <h2 class="fw-bold mb-3">Download the app and manage your plan <span
                        class="highlight">easily</span></h2>
                <p class="lead mb-4">Access mobile connectivity worldwide with our innovative eSIM solutions for
                    international travel — seamless setup, instant activation!</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="https://play.google.com/store/apps/details?id={{systemflag('androidPackageName')}}">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg"
                            alt="Get it on Google Play" class="store-badge" height="52">
                    </a>
                    <a href="https://apps.apple.com/app/id{{systemflag('iosAppId')}}">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg"
                            alt="Download on the App Store" class="store-badge" height="52">
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>



@endsection
