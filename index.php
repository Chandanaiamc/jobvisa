<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobVisa.lk — Find Your Dream Job Abroad</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f2438;
            --ink-soft: #3d5568;
            --brand: #0a5c6b;
            --brand-deep: #074552;
            --accent: #c9852a;
            --accent-hover: #b07322;
            --surface: #f3f7f8;
            --white: #ffffff;
            --line: rgba(15, 36, 56, 0.12);
            --shadow: 0 18px 40px rgba(7, 69, 82, 0.12);
            --radius: 10px;
            --max: 1100px;
            --nav-h: 72px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Outfit", sans-serif;
            color: var(--ink);
            background: var(--surface);
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .container {
            width: min(100% - 2rem, var(--max));
            margin-inline: auto;
        }

        /* —— Navigation —— */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--nav-h);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }

        .nav {
            height: var(--nav-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .logo {
            font-family: "Fraunces", serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--brand-deep);
            letter-spacing: -0.02em;
        }

        .logo span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.75rem;
            list-style: none;
        }

        .nav-links a {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--ink-soft);
            position: relative;
            transition: color 0.25s ease;
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 100%;
            height: 2px;
            background: var(--brand);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.25s ease;
        }

        .nav-links a:hover {
            color: var(--brand);
        }

        .nav-links a:hover::after {
            transform: scaleX(1);
        }

        .nav-cta {
            display: inline-flex;
            align-items: center;
            padding: 0.55rem 1.1rem;
            background: var(--brand);
            color: var(--white) !important;
            border-radius: var(--radius);
            font-weight: 600;
            transition: background 0.25s ease, transform 0.25s ease;
        }

        .nav-cta::after {
            display: none;
        }

        .nav-cta:hover {
            background: var(--brand-deep);
            color: var(--white) !important;
            transform: translateY(-1px);
        }

        .nav-toggle {
            display: none;
            width: 42px;
            height: 42px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--white);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 5px;
        }

        .nav-toggle span {
            display: block;
            width: 18px;
            height: 2px;
            background: var(--ink);
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .nav-toggle[aria-expanded="true"] span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }

        .nav-toggle[aria-expanded="true"] span:nth-child(2) {
            opacity: 0;
        }

        .nav-toggle[aria-expanded="true"] span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }

        /* —— Hero —— */
        .hero {
            position: relative;
            min-height: calc(100vh - var(--nav-h));
            display: grid;
            align-items: end;
            overflow: hidden;
            color: var(--white);
            background:
                linear-gradient(115deg, rgba(7, 69, 82, 0.88) 0%, rgba(15, 36, 56, 0.55) 48%, rgba(10, 92, 107, 0.35) 100%),
                url("https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1920&q=80") center / cover no-repeat;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 80%, rgba(201, 133, 42, 0.18), transparent 45%),
                linear-gradient(to top, rgba(7, 69, 82, 0.55), transparent 40%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            padding: 4.5rem 0 5rem;
            max-width: 38rem;
            animation: rise-in 0.9s ease both;
        }

        .hero-brand {
            font-family: "Fraunces", serif;
            font-size: clamp(2.6rem, 7vw, 4.2rem);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
        }

        .hero-brand span {
            color: #f0c57a;
        }

        .hero h1 {
            font-family: "Outfit", sans-serif;
            font-size: clamp(1.35rem, 3.2vw, 1.85rem);
            font-weight: 600;
            line-height: 1.25;
            margin-bottom: 0.85rem;
            max-width: 18ch;
        }

        .hero p {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.88);
            margin-bottom: 1.75rem;
            max-width: 34ch;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0.75rem 1.4rem;
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            border: 2px solid transparent;
            cursor: pointer;
            transition: background 0.25s ease, color 0.25s ease, border-color 0.25s ease, transform 0.25s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--accent);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: transparent;
            color: var(--white);
            border-color: rgba(255, 255, 255, 0.7);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--white);
        }

        /* —— Sections —— */
        .section {
            padding: 4.5rem 0;
        }

        .section-alt {
            background: var(--white);
        }

        .section-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--brand);
            margin-bottom: 0.65rem;
        }

        .section h2 {
            font-family: "Fraunces", serif;
            font-size: clamp(1.7rem, 3.5vw, 2.25rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 0.85rem;
            max-width: 18ch;
        }

        .section-lead {
            color: var(--ink-soft);
            font-size: 1.05rem;
            max-width: 42ch;
            margin-bottom: 1.5rem;
        }

        .split {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 2.5rem;
            align-items: center;
        }

        .split-visual {
            min-height: 280px;
            border-radius: 14px;
            background:
                linear-gradient(145deg, rgba(10, 92, 107, 0.82), rgba(15, 36, 56, 0.55)),
                url("https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1000&q=80") center / cover no-repeat;
            animation: fade-up 1s ease both;
        }

        .split-visual.employers {
            background:
                linear-gradient(145deg, rgba(201, 133, 42, 0.75), rgba(7, 69, 82, 0.65)),
                url("https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=1000&q=80") center / cover no-repeat;
        }

        .feature-list {
            list-style: none;
            display: grid;
            gap: 0.85rem;
            margin-bottom: 1.75rem;
        }

        .feature-list li {
            position: relative;
            padding-left: 1.35rem;
            color: var(--ink-soft);
        }

        .feature-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.55em;
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 2px;
            background: var(--brand);
        }

        .btn-outline {
            background: transparent;
            color: var(--brand);
            border-color: var(--brand);
        }

        .btn-outline:hover {
            background: var(--brand);
            color: var(--white);
        }

        /* —— Footer —— */
        .site-footer {
            background: var(--ink);
            color: rgba(255, 255, 255, 0.78);
            padding: 2.75rem 0 1.75rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .footer-brand {
            font-family: "Fraunces", serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.65rem;
        }

        .footer-brand span {
            color: #f0c57a;
        }

        .footer-grid p {
            max-width: 28ch;
            font-size: 0.95rem;
        }

        .footer-grid h3 {
            color: var(--white);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.85rem;
        }

        .footer-grid ul {
            list-style: none;
            display: grid;
            gap: 0.5rem;
        }

        .footer-grid a {
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .footer-grid a:hover {
            color: #f0c57a;
        }

        .footer-bottom {
            padding-top: 1.25rem;
            font-size: 0.85rem;
            text-align: center;
        }

        /* —— Motion —— */
        @keyframes rise-in {
            from {
                opacity: 0;
                transform: translateY(28px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fade-up {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reveal {
            animation: fade-up 0.8s ease both;
        }

        /* —— Responsive —— */
        @media (max-width: 860px) {
            .nav-toggle {
                display: inline-flex;
            }

            .nav-links {
                position: absolute;
                top: var(--nav-h);
                left: 0;
                right: 0;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                padding: 0.75rem 1rem 1rem;
                background: var(--white);
                border-bottom: 1px solid var(--line);
                box-shadow: var(--shadow);
                display: none;
            }

            .nav-links.is-open {
                display: flex;
            }

            .nav-links li a {
                display: block;
                padding: 0.85rem 0.5rem;
            }

            .nav-cta {
                justify-content: center;
                margin-top: 0.35rem;
            }

            .split {
                grid-template-columns: 1fr;
            }

            .split-visual {
                min-height: 220px;
                order: -1;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 1.75rem;
            }
        }

        @media (max-width: 520px) {
            .hero-inner {
                padding: 3.25rem 0 3.75rem;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .section {
                padding: 3.25rem 0;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="logo" href="index.php">JobVisa<span>.lk</span></a>
            <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="primary-nav">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links" id="primary-nav">
                <li><a href="#jobs">Jobs</a></li>
                <li><a href="#seekers">Job Seekers</a></li>
                <li><a href="#employers">Employers</a></li>
                <li><a class="nav-cta" href="#employers">Post a Job</a></li>
            </ul>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-inner">
                <p class="hero-brand">JobVisa<span>.lk</span></p>
                <h1>Find Your Dream Job Abroad</h1>
                <p>Connect with verified overseas opportunities and employers who value Sri Lankan talent.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#jobs">Find Jobs</a>
                    <a class="btn btn-secondary" href="#employers">Post a Job</a>
                </div>
            </div>
        </section>

        <section class="section" id="seekers">
            <div class="container split reveal">
                <div>
                    <span class="section-label">For job seekers</span>
                    <h2>Build a career beyond borders</h2>
                    <p class="section-lead">
                        Browse overseas roles, prepare your profile, and take the next step toward working abroad with clarity and confidence.
                    </p>
                    <ul class="feature-list">
                        <li>Discover jobs matched to your skills and experience</li>
                        <li>Understand visa-friendly opportunities more clearly</li>
                        <li>Apply directly to trusted employers and recruiters</li>
                    </ul>
                    <a class="btn btn-outline" href="#jobs">Browse openings</a>
                </div>
                <div class="split-visual" role="img" aria-label="Professionals collaborating in a modern workplace"></div>
            </div>
        </section>

        <section class="section section-alt" id="employers">
            <div class="container split reveal">
                <div class="split-visual employers" role="img" aria-label="Employers meeting with candidates"></div>
                <div>
                    <span class="section-label">For employers</span>
                    <h2>Hire skilled talent from Sri Lanka</h2>
                    <p class="section-lead">
                        Reach motivated candidates ready for international roles. Post openings and connect with people prepared to grow with your team.
                    </p>
                    <ul class="feature-list">
                        <li>Publish job listings quickly and clearly</li>
                        <li>Reach candidates seeking overseas careers</li>
                        <li>Build a stronger hiring pipeline with less friction</li>
                    </ul>
                    <a class="btn btn-outline" href="#employers">Post a Job</a>
                </div>
            </div>
        </section>

        <section class="section" id="jobs">
            <div class="container reveal">
                <span class="section-label">Opportunities</span>
                <h2>Your next role starts here</h2>
                <p class="section-lead">
                    Job listings will appear here soon. For now, explore how JobVisa.lk supports both seekers and employers.
                </p>
                <a class="btn btn-primary" href="#seekers">Learn more</a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">JobVisa<span>.lk</span></div>
                    <p>Helping Sri Lankans find meaningful work abroad, and helping employers hire with confidence.</p>
                </div>
                <div>
                    <h3>Explore</h3>
                    <ul>
                        <li><a href="#jobs">Find Jobs</a></li>
                        <li><a href="#seekers">Job Seekers</a></li>
                        <li><a href="#employers">Employers</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Contact</h3>
                    <ul>
                        <li><a href="mailto:hello@jobvisa.lk">hello@jobvisa.lk</a></li>
                        <li>Colombo, Sri Lanka</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JobVisa.lk. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.querySelector('.nav-toggle');
            var links = document.getElementById('primary-nav');
            if (!toggle || !links) return;

            toggle.addEventListener('click', function () {
                var open = links.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            links.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    links.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                });
            });
        })();
    </script>
</body>
</html>
