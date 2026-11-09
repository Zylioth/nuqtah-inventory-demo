<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuqtah Inventory System</title>
    <meta name="description" content="Nuqtah is the IT Inventory Management System for ITQSHHB. Request campus IT equipment, follow live stock, and return it on time.">
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">

    <!-- Fonts: Outfit (display + UI) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons: used by the shared footer (geo glyph) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">

<style>
/* ==========================================================================
   Nuqtah landing page — scoped to .nq-page, palette locked to brand teal
   ========================================================================== */
:root {
    --nq-teal: #00796B;
    --nq-deep: #004D40;
    --nq-paper: #F8FAFC;
    --nq-line: rgba(0, 77, 64, 0.14);
    --nq-body: rgba(0, 77, 64, 0.80);
    --nq-tint: rgba(0, 121, 107, 0.08);
    --nq-accent: #A5D6A7;
    --nq-ease: cubic-bezier(0.2, 0.7, 0.3, 1);
}

.nq-page {
    background-color: var(--nq-paper);
    color: var(--nq-body);
    font-family: 'Outfit', 'Segoe UI', system-ui, -apple-system, sans-serif;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

.nq-page h2,
.nq-page h3 {
    color: var(--nq-deep);
    letter-spacing: -0.02em;
}

.nq-page .nq-hero-title {
    color: #ffffff;
    letter-spacing: -0.03em;
}

.nq-page a { text-decoration: none; }

/* Skip link ---------------------------------------------------------------- */
.nq-skip {
    position: absolute;
    left: -9999px;
    top: 0;
    z-index: 2000;
    background: #ffffff;
    color: var(--nq-deep);
    padding: 0.75rem 1.25rem;
    border-radius: 0 0 12px 0;
    font-weight: 600;
}

.nq-skip:focus {
    left: 0;
    outline: 3px solid var(--nq-teal);
    outline-offset: 2px;
}

/* Navigation --------------------------------------------------------------- */
.nq-nav {
    position: fixed;
    inset: 0 0 auto 0;
    z-index: 1030;
    padding: 0.9rem 0;
    background: linear-gradient(180deg, rgba(0, 45, 38, 0.62) 0%, rgba(0, 45, 38, 0) 100%);
    transition: background-color 0.3s ease, box-shadow 0.3s ease, padding 0.3s ease;
}

.nq-nav.is-scrolled {
    padding: 0.55rem 0;
    background-color: rgba(0, 61, 51, 0.94);
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
}

.nq-nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.nq-brand img {
    height: 36px;
    width: auto;
    display: block;
}

.nq-nav-links { display: none; }

.nq-nav-links a {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.92rem;
    font-weight: 500;
    padding: 0.35rem 0;
    position: relative;
}

.nq-nav-links a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 2px;
    background: var(--nq-accent);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.25s var(--nq-ease);
}

.nq-nav-links a:hover,
.nq-nav-links a:focus-visible { color: #ffffff; }

.nq-nav-links a:hover::after,
.nq-nav-links a:focus-visible::after { transform: scaleX(1); }

.nq-auth {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nq-who {
    display: none;
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.875rem;
}

.nq-who strong { color: #ffffff; font-weight: 600; }

/* Buttons ------------------------------------------------------------------ */
.nq-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 48px;
    padding: 0.7rem 1.55rem;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.1;
    transition: transform 0.22s var(--nq-ease), background-color 0.22s ease,
                border-color 0.22s ease, box-shadow 0.22s ease, color 0.22s ease;
}

.nq-btn:focus-visible {
    outline: 3px solid #ffffff;
    outline-offset: 3px;
    box-shadow: 0 0 0 6px rgba(0, 121, 107, 0.5);
}

.nq-btn-solid {
    background-color: #ffffff;
    color: var(--nq-deep);
    box-shadow: 0 10px 24px rgba(0, 30, 25, 0.22);
}

.nq-btn-solid:hover,
.nq-btn-solid:focus-visible {
    color: var(--nq-deep);
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(0, 30, 25, 0.28);
}

.nq-btn-teal {
    background-color: var(--nq-teal);
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(0, 77, 64, 0.24);
}

.nq-btn-teal:hover,
.nq-btn-teal:focus-visible {
    background-color: var(--nq-deep);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(0, 77, 64, 0.3);
}

.nq-btn-ghost {
    background-color: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.5);
    color: #ffffff;
}

.nq-btn-ghost:hover,
.nq-btn-ghost:focus-visible {
    background-color: rgba(255, 255, 255, 0.16);
    border-color: #ffffff;
    color: #ffffff;
    transform: translateY(-2px);
}

.nq-btn-outline {
    background-color: transparent;
    border-color: rgba(0, 121, 107, 0.45);
    color: var(--nq-deep);
}

.nq-btn-outline:hover,
.nq-btn-outline:focus-visible {
    background-color: rgba(0, 121, 107, 0.08);
    border-color: var(--nq-teal);
    color: var(--nq-deep);
    transform: translateY(-2px);
}

.nq-btn-sm {
    min-height: 42px;
    padding: 0.45rem 1.1rem;
    font-size: 0.875rem;
}

.nq-btn-lg {
    min-height: 54px;
    padding: 0.95rem 2rem;
    font-size: 1rem;
}

/* Hero --------------------------------------------------------------------- */
.nq-hero {
    position: relative;
    isolation: isolate;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100svh;
    padding: 132px 0 96px;
    text-align: center;
    color: #ffffff;
    background-color: var(--nq-deep);
    overflow: hidden;
}

.nq-hero-video {
    position: absolute;
    inset: 0;
    z-index: -2;
    width: 100%;
    height: 100%;
    object-fit: cover;
    animation: nqVideoIn 2.4s var(--nq-ease) both;
}

.nq-hero-veil {
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        linear-gradient(180deg, rgba(0, 45, 38, 0.86) 0%, rgba(0, 55, 46, 0.66) 38%, rgba(0, 22, 18, 0.90) 100%),
        radial-gradient(90% 70% at 50% 45%, rgba(0, 40, 33, 0.26), rgba(0, 40, 33, 0) 70%),
        radial-gradient(120% 80% at 50% 0%, rgba(0, 121, 107, 0.34), rgba(0, 121, 107, 0) 62%);
}

.nq-hero-inner {
    position: relative;
    max-width: 940px;
}

.nq-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    margin-bottom: 1.4rem;
    color: rgba(255, 255, 255, 0.74);
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

.nq-eyebrow::before,
.nq-eyebrow::after {
    content: "";
    width: 26px;
    height: 1px;
    background: rgba(255, 255, 255, 0.4);
}

.nq-logos {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.6rem;
    margin-bottom: 1.75rem;
}

.nq-logos img {
    width: auto;
    max-width: 100%;
    filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.45));
}

.nq-logo-nuqtah { height: 62px; }
.nq-logo-itq { height: 78px; }

.nq-logos .nq-divider {
    width: 1px;
    height: 44px;
    background: rgba(255, 255, 255, 0.38);
}

.nq-hero-title {
    max-width: 18ch;
    margin: 0 auto 1.15rem;
    color: #ffffff;
    font-size: clamp(2.15rem, 5.4vw, 4rem);
    font-weight: 700;
    line-height: 1.04;
    letter-spacing: -0.03em;
}

.nq-hero-title span { color: var(--nq-accent); }

.nq-hero-lede {
    max-width: 56ch;
    margin: 0 auto 2rem;
    color: rgba(255, 255, 255, 0.86);
    font-size: clamp(1rem, 1.35vw, 1.12rem);
    font-weight: 300;
    line-height: 1.65;
}

.nq-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.nq-scroll {
    position: absolute;
    left: 50%;
    bottom: 24px;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    transition: color 0.22s ease;
}

.nq-scroll:hover,
.nq-scroll:focus-visible { color: #ffffff; }

/* Motion: entrance for hero children, slow settle for the video ------------ */
@keyframes nqRise {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: none; }
}

@keyframes nqVideoIn {
    from { transform: scale(1.06); }
    to   { transform: scale(1); }
}

.nq-hero-inner > * {
    animation: nqRise 0.7s var(--nq-ease) both;
}

.nq-hero-inner > *:nth-child(2) { animation-delay: 0.08s; }
.nq-hero-inner > *:nth-child(3) { animation-delay: 0.16s; }
.nq-hero-inner > *:nth-child(4) { animation-delay: 0.24s; }
.nq-hero-inner > *:nth-child(5) { animation-delay: 0.32s; }

/* Scroll reveal (only when JS is on; no-JS keeps content visible) --------- */
.js [data-reveal] {
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 0.5s var(--nq-ease), transform 0.5s var(--nq-ease);
    transition-delay: var(--d, 0ms);
}

.js [data-reveal].is-in {
    opacity: 1;
    transform: none;
}

/* Section shell ------------------------------------------------------------ */
.nq-section {
    padding: clamp(56px, 8vw, 104px) 0;
    scroll-margin-top: 88px;
}

.nq-flow { background-color: #ffffff; border-top: 1px solid var(--nq-line); }
.nq-caps { background-color: var(--nq-paper); }

.nq-head {
    max-width: 660px;
    margin: 0 auto clamp(32px, 5vw, 56px);
    text-align: center;
}

.nq-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.9rem;
    color: var(--nq-teal);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.nq-kicker::before {
    content: "";
    width: 22px;
    height: 1px;
    background: currentColor;
}

.nq-head h2 {
    margin-bottom: 0.6rem;
    font-size: clamp(1.7rem, 3vw, 2.4rem);
    font-weight: 700;
    line-height: 1.15;
}

.nq-sub {
    margin: 0;
    color: var(--nq-body);
    font-size: 1rem;
    line-height: 1.6;
}

/* Workflow steps ----------------------------------------------------------- */
.nq-steps {
    position: relative;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: clamp(16px, 2.5vw, 32px);
    margin: 0;
    padding: 0;
    list-style: none;
}

.nq-steps::before {
    content: "";
    position: absolute;
    top: 26px;
    left: 12.5%;
    right: 12.5%;
    height: 1px;
    background: repeating-linear-gradient(90deg, rgba(0, 121, 107, 0.28) 0 6px, transparent 6px 13px);
}

.nq-step {
    position: relative;
    text-align: center;
}

.nq-step-num {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 54px;
    height: 54px;
    margin-bottom: 1.1rem;
    border: 1px solid rgba(0, 121, 107, 0.35);
    border-radius: 50%;
    background-color: #ffffff;
    color: var(--nq-teal);
    font-size: 1.05rem;
    font-weight: 700;
    transition: background-color 0.25s ease, color 0.25s ease,
                transform 0.25s var(--nq-ease), box-shadow 0.25s ease;
}

.nq-step:hover .nq-step-num {
    background-color: var(--nq-teal);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(0, 121, 107, 0.24);
}

.nq-step h3 {
    margin-bottom: 0.45rem;
    font-size: 1.02rem;
    font-weight: 600;
}

.nq-step p {
    max-width: 26ch;
    margin: 0 auto;
    color: var(--nq-body);
    font-size: 0.92rem;
    line-height: 1.62;
}

/* Capability cards --------------------------------------------------------- */
.nq-cap-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: clamp(16px, 2vw, 24px);
}

.nq-cap {
    position: relative;
    padding: clamp(22px, 2.4vw, 30px);
    border: 1px solid var(--nq-line);
    border-radius: 20px;
    background-color: #ffffff;
    transition: transform 0.3s var(--nq-ease), box-shadow 0.3s var(--nq-ease),
                border-color 0.3s ease;
}

.nq-cap:hover {
    transform: translateY(-4px);
    border-color: rgba(0, 121, 107, 0.4);
    box-shadow: 0 18px 40px rgba(0, 77, 64, 0.12);
}

.nq-cap-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    margin-bottom: 1.1rem;
    border-radius: 14px;
    background-color: var(--nq-tint);
    color: var(--nq-teal);
    font-size: 1.3rem;
    transition: background-color 0.25s ease, color 0.25s ease;
}

.nq-cap:hover .nq-cap-icon {
    background-color: var(--nq-teal);
    color: #ffffff;
}

.nq-cap-num {
    position: absolute;
    top: 22px;
    right: 24px;
    color: rgba(0, 77, 64, 0.45);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.nq-cap h3 {
    margin-bottom: 0.5rem;
    font-size: 1.05rem;
    font-weight: 600;
}

.nq-cap p {
    margin: 0;
    color: var(--nq-body);
    font-size: 0.92rem;
    line-height: 1.62;
}

/* Closing call to action --------------------------------------------------- */
.nq-band { padding: 0 0 clamp(56px, 8vw, 96px); background-color: var(--nq-paper); }

.nq-band-inner {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    padding: clamp(28px, 4vw, 52px);
    border: 1px solid rgba(0, 121, 107, 0.22);
    border-radius: 28px;
    background: linear-gradient(135deg, rgba(0, 121, 107, 0.10), rgba(0, 77, 64, 0.05));
}

.nq-band-inner h2 {
    margin-bottom: 0.4rem;
    font-size: clamp(1.4rem, 2.4vw, 1.85rem);
    font-weight: 700;
    color: var(--nq-deep);
}

.nq-band-inner p {
    margin: 0;
    max-width: 46ch;
    color: var(--nq-body);
    font-size: 0.95rem;
    line-height: 1.6;
}

.nq-band-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

/* Shared footer: keep it intact, just let it wrap on small phones -------- */
.nq-page footer img { max-width: 100%; }

/* Responsive --------------------------------------------------------------- */
@media (min-width: 768px) {
    .nq-who { display: inline; }

    .nq-band-inner {
        flex-direction: row;
        align-items: center;
    }

    .nq-band-inner > div:first-child { min-width: 0; }

    .nq-band-actions {
        flex-shrink: 0;
        flex-wrap: nowrap;
    }
}

@media (min-width: 992px) {
    .nq-nav-links {
        display: flex;
        align-items: center;
        gap: 1.75rem;
    }
}

@media (max-width: 991px) {
    .nq-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .nq-steps::before { display: none; }
    .nq-cap-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 767px) {
    .nq-hero {
        min-height: 92svh;
        padding: 108px 0 84px;
    }

    .nq-logo-nuqtah { height: 40px; }
    .nq-logo-itq { height: 52px; }
    .nq-logos { gap: 1rem; margin-bottom: 1.35rem; }
    .nq-logos .nq-divider { height: 34px; }

    .nq-hero-title { letter-spacing: -0.02em; }

    .nq-step p { max-width: 34ch; }

    .nq-scroll { display: none; }
}

@media (max-width: 575px) {
    .nq-cap-grid { grid-template-columns: minmax(0, 1fr); }
    .nq-steps { grid-template-columns: minmax(0, 1fr); }
    .nq-hero-actions { width: 100%; }
    .nq-hero-actions .nq-btn { width: 100%; }
    .nq-band-actions { width: 100%; }
    .nq-band-actions .nq-btn { flex: 1 1 auto; }

    /* Two wide logo files do not fit side by side on a phone: stack them */
    .nq-logos {
        flex-direction: column;
        gap: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .nq-logos .nq-divider {
        width: 64px;
        height: 1px;
    }

    .nq-logo-nuqtah { height: 44px; }
    .nq-logo-itq { height: 54px; }

    .nq-page footer .d-flex {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.75rem 0;
    }

    .nq-page footer .border-start { display: none; }
}

/* Reduced motion ----------------------------------------------------------- */
@media (prefers-reduced-motion: no-preference) {
    html { scroll-behavior: smooth; }
}

@media (prefers-reduced-motion: reduce) {
    .nq-page *,
    .nq-page *::before,
    .nq-page *::after {
        animation-duration: 0.001ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.001ms !important;
        scroll-behavior: auto !important;
    }

    .nq-hero-video { animation: none; }
    .js [data-reveal],
    .js [data-reveal].is-in {
        opacity: 1;
        transform: none;
    }
}
</style>
</head>

<body class="nq-page">

<a class="nq-skip" href="#main">Skip to content</a>

<nav class="nq-nav" id="nqNav" aria-label="Primary">
    <div class="container nq-nav-inner">
        <a class="nq-brand" href="index.php" aria-label="Nuqtah home">
            <img src="assets/img/logoNuqtah_White.png" alt="Nuqtah" height="36">
        </a>

        <div class="nq-nav-links">
            <a href="#workflow">How it works</a>
            <a href="#features">Capabilities</a>
        </div>

        <div class="nq-auth">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nq-who">Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <a href="actions/logout.php" class="nq-btn nq-btn-ghost nq-btn-sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nq-btn nq-btn-ghost nq-btn-sm">Login</a>
                <a href="signup.php" class="nq-btn nq-btn-solid nq-btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<header class="nq-hero">
    <video class="nq-hero-video" autoplay muted loop playsinline preload="metadata"
           poster="assets/img/Tahfiz_front.jpg" aria-hidden="true" tabindex="-1">
        <source src="assets/video/Front_slowfront_boomerang.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="nq-hero-veil" aria-hidden="true"></div>

    <div class="container nq-hero-inner">
        <p class="nq-eyebrow">ITQSHHB &middot; Nuqtah</p>

        <div class="nq-logos">
            <img class="nq-logo-nuqtah" src="assets/img/logoNuqtah_White.png" alt="Nuqtah Logo">
            <div class="nq-divider" aria-hidden="true"></div>
            <img class="nq-logo-itq" src="assets/img/ITQSHHBLogo.png" alt="ITQSHHB Logo">
        </div>

        <h1 class="nq-hero-title">Campus IT gear,<br><span>tracked end to end.</span></h1>

        <p class="nq-hero-lede">
            An IT Inventory Management System built for ITQSHHB — request equipment,
            follow live stock, and return it on time. No paper forms.
        </p>

        <div class="nq-hero-actions">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="nq-btn nq-btn-solid nq-btn-lg">Login to Access Inventory</a>
                <a href="signup.php" class="nq-btn nq-btn-ghost nq-btn-lg">Create an account</a>
            <?php else: ?>
                <a href="inventory_list.php" class="nq-btn nq-btn-solid nq-btn-lg">View Inventory</a>
            <?php endif; ?>
        </div>
    </div>

    <a class="nq-scroll" href="#workflow">
        <span>How it works</span>
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M8 3v10M3.5 8.5 8 13l4.5-4.5" stroke="currentColor" stroke-width="1.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
</header>

<main id="main">

    <!-- Workflow -->
    <section class="nq-section nq-flow" id="workflow" aria-labelledby="workflow-title">
        <div class="container">
            <div class="nq-head" data-reveal>
                <p class="nq-kicker">Workflow</p>
                <h2 id="workflow-title">How digital borrowing works</h2>
                <p class="nq-sub">A seamless paperless experience from request to return.</p>
            </div>

            <ol class="nq-steps">
                <li class="nq-step" data-reveal>
                    <span class="nq-step-num" aria-hidden="true">1</span>
                    <h3>Sign In</h3>
                    <p>Register with your institutional credentials to access the central catalog.</p>
                </li>
                <li class="nq-step" data-reveal>
                    <span class="nq-step-num" aria-hidden="true">2</span>
                    <h3>Reserve Items</h3>
                    <p>Browse live stock counts, add items to your basket, and specify a pickup time.</p>
                </li>
                <li class="nq-step" data-reveal>
                    <span class="nq-step-num" aria-hidden="true">3</span>
                    <h3>Inspection</h3>
                    <p>Present your request ID at the desk, verify the item condition, and collect.</p>
                </li>
                <li class="nq-step" data-reveal>
                    <span class="nq-step-num" aria-hidden="true">4</span>
                    <h3>Safe Return</h3>
                    <p>Return the equipment on or before your return date to keep the inventory healthy.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Capabilities -->
    <section class="nq-section nq-caps" id="features" aria-labelledby="features-title">
        <div class="container">
            <div class="nq-head" data-reveal>
                <p class="nq-kicker">Capabilities</p>
                <h2 id="features-title">System capabilities</h2>
                <p class="nq-sub">Fully loaded with all the modules needed to run campus assets smoothly.</p>
            </div>

            <div class="nq-cap-grid">
                <article class="nq-cap" data-reveal>
                    <span class="nq-cap-num" aria-hidden="true">01</span>
                    <div class="nq-cap-icon"><i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i></div>
                    <h3>Borrow &amp; Return</h3>
                    <p>Move away from manual paper forms. Submit digital requests in seconds and track your return deadlines automatically.</p>
                </article>

                <article class="nq-cap" data-reveal>
                    <span class="nq-cap-num" aria-hidden="true">02</span>
                    <div class="nq-cap-icon"><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i></div>
                    <h3>Shop-like Interface</h3>
                    <p>Browse with a modern cart system. Add multiple items and submit in one single action.</p>
                </article>

                <article class="nq-cap" data-reveal>
                    <span class="nq-cap-num" aria-hidden="true">03</span>
                    <div class="nq-cap-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
                    <h3>Real-time Tracking</h3>
                    <p>Monitor real-time stock levels and the current deployment location of all borrowed IT assets.</p>
                </article>

                <article class="nq-cap" data-reveal>
                    <span class="nq-cap-num" aria-hidden="true">04</span>
                    <div class="nq-cap-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></div>
                    <h3>Manage</h3>
                    <p>Easily add, edit, or remove item parameters from the central operational database in seconds.</p>
                </article>

                <article class="nq-cap" data-reveal>
                    <span class="nq-cap-num" aria-hidden="true">05</span>
                    <div class="nq-cap-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div>
                    <h3>Admin Dashboard</h3>
                    <p>Track equipment with real-time dynamic statistics, charts, and transaction logs from one centralized screen.</p>
                </article>

                <article class="nq-cap" data-reveal>
                    <span class="nq-cap-num" aria-hidden="true">06</span>
                    <div class="nq-cap-icon"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></div>
                    <h3>Report</h3>
                    <p>Generate detailed inventory listings and audit reports for institutional administrative reviews.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Closing call to action -->
    <section class="nq-band" id="cta" aria-labelledby="band-title">
        <div class="container">
            <div class="nq-band-inner" data-reveal>
                <div>
                    <h2 id="band-title">Start your first request.</h2>
                    <p>Log in with your institutional account and reserve campus equipment in minutes.</p>
                </div>
                <div class="nq-band-actions">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="nq-btn nq-btn-teal">Login</a>
                        <a href="signup.php" class="nq-btn nq-btn-outline">Register</a>
                    <?php else: ?>
                        <a href="inventory_list.php" class="nq-btn nq-btn-teal">Browse Inventory</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>

<script>
(function () {
    var root = document.documentElement;
    root.classList.remove('no-js');
    root.classList.add('js');

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Ambient video: pause for motion-sensitive visitors */
    var video = document.querySelector('.nq-hero-video');
    if (reduceMotion && video) {
        video.pause();
    }

    /* Nav: solid once the hero starts scrolling away */
    var nav = document.getElementById('nqNav');
    if (nav) {
        var onScroll = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 16);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* Scroll reveal: one staggered pass, never re-triggered */
    var items = document.querySelectorAll('[data-reveal]');
    if (!items.length) { return; }

    if (reduceMotion || !('IntersectionObserver' in window)) {
        items.forEach(function (el) { el.classList.add('is-in'); });
        return;
    }

    items.forEach(function (el) {
        var siblings = Array.prototype.filter.call(el.parentElement.children, function (child) {
            return child.hasAttribute('data-reveal');
        });
        var index = siblings.indexOf(el);
        el.style.setProperty('--d', (Math.min(index, 5) * 70) + 'ms');
    });

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    items.forEach(function (el) { observer.observe(el); });
}());
</script>

</body>
</html>
