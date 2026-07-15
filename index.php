<?php
/**
 * Public Index / Home Page - Talent Institute
 */
require_once __DIR__ . '/config/config.php';

if (Auth::check()) {
    redirect('views/dashboard.php');
}

$schoolName   = Setting::get('school_name', 'Talent Institute');
$schoolEmail  = Setting::get('school_email', 'info@talentinstitute.edu');
$schoolPhone  = Setting::get('school_phone', '+252 61 000 0000');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Dark Mode Loader -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
            document.addEventListener('DOMContentLoaded', function() {
                document.body && document.body.setAttribute('data-theme', theme);
            });
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($schoolName) ?> — Languages & Scientific Subjects</title>
    <meta name="description" content="Talent Institute — A premier institution offering Languages & Scientific Subjects in a modern, professional environment.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* Brand */
            --blue-900: #1e3a8a;
            --blue-700: #1d4ed8;
            --blue-600: #2563eb;
            --blue-500: #3b82f6;
            --blue-400: #60a5fa;
            --blue-100: #dbeafe;
            --blue-50:  #eff6ff;
            --white:    #ffffff;
            --gray-50:  #f8fafc;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-500: #64748b;
            --gray-700: #334155;
            --gray-900: #0f172a;
            --gold:     #f59e0b;

            /* Fluid sizing scaling with window maximization/minimization */
            --fs-hero-title: clamp(2rem, 1.25rem + 3.5vw, 3.8rem);
            --fs-hero-title-ar: clamp(1.4rem, 1.1rem + 2.5vw, 2.6rem);
            --fs-section-title: clamp(1.6rem, 1.2rem + 2.2vw, 2.8rem);
            --fs-body: clamp(0.85rem, 0.8rem + 0.35vw, 1.15rem);
            --fs-nav-link: clamp(0.78rem, 0.74rem + 0.2vw, 1.05rem);
            --fs-btn: clamp(0.8rem, 0.76rem + 0.2vw, 1.1rem);
            --btn-pad-y: clamp(8px, 6px + 0.4vw, 15px);
            --btn-pad-x: clamp(16px, 12px + 1vw, 32px);
            --card-pad: clamp(18px, 12px + 1vw, 36px);
            --sect-pad: clamp(50px, 30px + 4vw, 110px);
        }

        /* Dark mode values */
        [data-theme="dark"] {
            --white:    #0b0f19;
            --gray-50:  #0d1424;
            --gray-100: #152238;
            --gray-300: #475569;
            --gray-500: #94a3b8;
            --gray-700: #cbd5e1;
            --gray-900: #f1f5f9;
            --blue-100: #1e293b;
            --blue-50:  #1e293b;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--white);
            color: var(--gray-700);
            overflow-x: hidden;
            line-height: 1.7;
            font-size: var(--fs-body);
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Dark theme specific fixes for elements */
        [data-theme="dark"] .site-navbar {
            background: rgba(11, 15, 25, 0.95);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        [data-theme="dark"] .feature-card {
            background: var(--gray-50);
            border-color: rgba(255, 255, 255, 0.05);
        }
        [data-theme="dark"] .about-img-main {
            background: linear-gradient(135deg, #0d1e3d, #1e3a8a);
        }
        [data-theme="dark"] .contact-form-card {
            background: var(--gray-50);
            border-color: rgba(255, 255, 255, 0.05);
        }
        [data-theme="dark"] .form-field input,
        [data-theme="dark"] .form-field textarea,
        [data-theme="dark"] .form-field select {
            background: #0b0f19;
            border-color: #2e3c60;
            color: var(--gray-700);
        }
        [data-theme="dark"] .mobile-menu {
            background: #0b0f19;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        [data-theme="dark"] .mobile-menu a {
            color: var(--gray-700);
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        [data-theme="dark"] .site-footer {
            background: #070a13;
        }
        [data-theme="dark"] .site-navbar .nav-hamburger span {
            background: var(--gray-900);
        }

        /* ─── HEADER & NAVBAR ────────────────── */
        .header-container {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000;
            width: 100%;
            transition: transform 0.3s ease;
        }

        .site-topbar {
            background: var(--blue-700);
            color: #fff;
            font-size: clamp(0.72rem, 0.7rem + 0.1vw, 0.88rem);
            padding: 8px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            transition: background 0.3s ease, color 0.3s ease;
        }
        .topbar-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topbar-link {
            color: #fff !important;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .topbar-link:hover { opacity: 0.85; }

        .site-navbar {
            position: relative;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(37,99,235,0.10);
            padding: 0;
            transition: box-shadow 0.3s, background 0.3s, border-color 0.3s;
        }
        .site-navbar.scrolled { box-shadow: 0 4px 24px rgba(30,58,138,0.10); }

        /* Dark mode overrides for topbar */
        [data-theme="dark"] .site-topbar {
            background: var(--gray-100) !important;
            color: var(--gray-900) !important;
            border-bottom-color: rgba(255,255,255,0.05);
        }
        [data-theme="dark"] .topbar-link {
            color: var(--gray-900) !important;
        }

        .navbar-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .nav-brand-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 1rem;
            flex-shrink: 0;
        }
        .nav-brand-text { line-height: 1.2; }
        .nav-brand-title {
            font-size: 0.95rem; font-weight: 700;
            color: var(--blue-900); display: block;
        }
        [data-theme="dark"] .nav-brand-title {
            color: var(--gray-900);
        }
        .nav-brand-sub {
            font-size: 0.68rem; color: var(--gray-500);
            font-weight: 500; display: block;
        }

        .nav-links {
            display: flex; align-items: center; gap: 6px;
            list-style: none;
        }
        .nav-links a {
            text-decoration: none; color: var(--gray-700);
            font-size: var(--fs-nav-link); font-weight: 500;
            padding: 7px 14px; border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-links a:hover { background: var(--blue-50); color: var(--blue-700); }

        .nav-login-group {
            display: flex; align-items: center; gap: 8px;
        }
        .btn-login-student {
            padding: var(--btn-pad-y) var(--btn-pad-x); border-radius: 8px;
            border: 1.5px solid var(--blue-600);
            color: var(--blue-600); font-size: var(--fs-btn); font-weight: 600;
            text-decoration: none; transition: all 0.2s;
            background: transparent;
            display: inline-flex; align-items: center;
        }
        .btn-login-student:hover {
            background: var(--blue-50); color: var(--blue-700);
        }
        .btn-login-teacher {
            padding: var(--btn-pad-y) var(--btn-pad-x); border-radius: 8px;
            background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
            color: #fff; font-size: var(--fs-btn); font-weight: 600;
            text-decoration: none; transition: all 0.2s;
            border: none;
            display: inline-flex; align-items: center;
        }
        .btn-login-teacher:hover {
            opacity: 0.88; color: #fff; transform: translateY(-1px);
        }

        /* Hamburger */
        .nav-hamburger {
            display: none;
            flex-direction: column; gap: 5px;
            background: none; border: none; cursor: pointer;
            padding: 8px;
        }
        .nav-hamburger span {
            display: block; width: 24px; height: 2px;
            background: var(--blue-900); border-radius: 2px;
            transition: all 0.3s;
        }

        /* Mobile menu */
        .mobile-menu {
            display: none;
            position: fixed; top: 104px; left: 0; right: 0;
            background: #fff;
            border-bottom: 1px solid var(--blue-100);
            padding: 16px 24px 24px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            z-index: 999;
        }
        .mobile-menu.open { display: block; }
        .mobile-menu a {
            display: block; padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700); text-decoration: none;
            font-weight: 500; font-size: 0.95rem;
        }
        .mobile-menu a:last-child { border: none; }
        .mobile-menu .mobile-login-btns {
            display: flex; flex-direction: column; gap: 10px; margin-top: 16px;
        }
        .mobile-menu .mobile-login-btns a {
            border: none; padding: 12px 0; text-align: center;
            border-radius: 10px; font-weight: 600;
        }
        .mobile-menu .mobile-login-btns .btn-student-m {
            background: var(--blue-50); color: var(--blue-700);
            border: 1.5px solid var(--blue-200, #bfdbfe);
        }
        .mobile-menu .mobile-login-btns .btn-teacher-m {
            background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
            color: #fff;
        }

        /* ─── HERO SECTION ────────────────────── */
        .hero {
            min-height: 100vh;
            background: linear-gradient(140deg, var(--blue-900) 0%, var(--blue-700) 50%, #1e40af 100%);
            display: flex; align-items: center;
            position: relative; overflow: hidden;
            padding: clamp(120px, 90px + 4vw, 160px) 24px 60px;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 80% 20%, rgba(96,165,250,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(30,58,138,0.4) 0%, transparent 55%);
        }

        /* Animated circles */
        .hero-circle {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.05);
            animation: floatCircle ease-in-out infinite alternate;
        }
        .hero-circle-1 { width: 600px; height: 600px; top: -200px; right: -150px; animation-duration: 8s; }
        .hero-circle-2 { width: 350px; height: 350px; bottom: -100px; left: -80px; animation-duration: 11s; animation-delay: 2s; }
        .hero-circle-3 { width: 200px; height: 200px; top: 40%; right: 15%; animation-duration: 6s; animation-delay: 1s; background: rgba(255,255,255,0.07); }

        @keyframes floatCircle {
            from { transform: translateY(0) scale(1); }
            to   { transform: translateY(-30px) scale(1.05); }
        }

        .hero-content {
            position: relative; z-index: 2;
            max-width: 1280px; margin: 0 auto; width: 100%;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px; align-items: center;
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 16px; border-radius: 100px;
            color: #bfdbfe; font-size: 0.8rem; font-weight: 600;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
        }
        .hero-badge i { color: var(--gold); }

        .hero-title {
            font-size: var(--fs-hero-title);
            font-weight: 900; color: #fff;
            line-height: 1.15; margin-bottom: 12px;
            letter-spacing: -1px;
        }
        .hero-title-arabic {
            font-size: var(--fs-hero-title-ar);
            font-weight: 700; color: var(--blue-400);
            direction: rtl; margin-bottom: 24px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-subtitle {
            font-size: clamp(0.95rem, 0.9rem + 0.25vw, 1.30rem); color: rgba(255,255,255,0.8);
            margin-bottom: 40px; max-width: 520px;
            line-height: 1.8;
        }

        .hero-cta {
            display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
        }
        .btn-hero-primary {
            padding: var(--btn-pad-y) var(--btn-pad-x); border-radius: 12px;
            background: #fff; color: var(--blue-900);
            font-weight: 700; font-size: var(--fs-btn);
            text-decoration: none; transition: all 0.25s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            color: var(--blue-900);
        }
        .btn-hero-secondary {
            padding: var(--btn-pad-y) var(--btn-pad-x); border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.4);
            color: #fff; font-weight: 600; font-size: var(--fs-btn);
            text-decoration: none; transition: all 0.25s;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-hero-secondary:hover {
            background: rgba(255,255,255,0.12);
            color: #fff; transform: translateY(-2px);
        }

        /* Hero stats */
        .hero-stats {
            display: flex; gap: clamp(16px, 10px + 1.5vw, 40px); margin-top: 48px;
        }
        .hero-stat-num {
            font-size: clamp(1.5rem, 1.25rem + 1vw, 2.5rem); font-weight: 800; color: #fff;
            line-height: 1;
        }
        .hero-stat-label {
            font-size: clamp(0.68rem, 0.65rem + 0.1vw, 0.85rem); color: rgba(255,255,255,0.6);
            font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* Hero visual card */
        .hero-visual {
            position: relative; z-index: 2;
        }
        .hero-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 24px; padding: var(--card-pad);
        }
        .hero-feature-item {
            display: flex; align-items: center; gap: 16px;
            padding: 16px; background: rgba(255,255,255,0.08);
            border-radius: 14px; margin-bottom: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.25s;
        }
        .hero-feature-item:hover { background: rgba(255,255,255,0.14); }
        .hero-feature-item:last-child { margin-bottom: 0; }
        .hero-feature-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .hero-feature-text h5 {
            color: #fff; font-weight: 600; font-size: 0.9rem; margin: 0;
        }
        .hero-feature-text p {
            color: rgba(255,255,255,0.65); font-size: 0.78rem; margin: 0;
        }

        /* ─── SECTION COMMON ──────────────────── */
        .section { padding: var(--sect-pad) 24px; }
        .section-alt { background: var(--gray-50); }

        .section-badge {
            display: inline-block;
            background: var(--blue-100); color: var(--blue-700);
            padding: 4px 14px; border-radius: 100px;
            font-size: clamp(0.68rem, 0.65rem + 0.1vw, 0.85rem); font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: var(--fs-section-title);
            font-weight: 800; color: var(--gray-900);
            line-height: 1.2; margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        .section-sub {
            color: var(--gray-500); font-size: clamp(0.85rem, 0.8rem + 0.2vw, 1.15rem);
            max-width: 580px; margin: 0 auto;
        }
        .section-inner { max-width: 1280px; margin: 0 auto; }

        /* ─── FEATURES SECTION ────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px; margin-top: 56px;
        }
        .feature-card {
            background: var(--white); border-radius: 20px;
            padding: var(--card-pad); border: 1px solid var(--gray-100);
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--blue-600), var(--blue-400));
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.3s;
        }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(37,99,235,0.08); }
        .feature-card:hover::before { transform: scaleX(1); }

        .feature-icon {
            width: 56px; height: 56px; border-radius: 16px;
            background: var(--blue-50);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: var(--blue-600); margin-bottom: 20px;
        }
        .feature-card h3 {
            font-size: clamp(0.95rem, 0.9rem + 0.2vw, 1.25rem); font-weight: 700;
            color: var(--gray-900); margin-bottom: 10px;
        }
        .feature-card p {
            color: var(--gray-500); font-size: clamp(0.8rem, 0.76rem + 0.15vw, 1rem); line-height: 1.7; margin: 0;
        }

        /* ─── ABOUT SECTION ───────────────────── */
        #about .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px; align-items: center;
            margin-top: 0;
        }
        .about-img-wrapper {
            position: relative;
        }
        .about-img-main {
            width: 100%; border-radius: 24px;
            background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
            padding: 48px; text-align: center;
            color: #fff;
        }
        .about-img-main i { font-size: 6rem; opacity: 0.9; }
        .about-img-main h2 { font-size: clamp(1.4rem, 1.2rem + 1vw, 2.2rem); font-weight: 800; margin-top: 16px; }
        .about-img-main p { opacity: 0.75; margin-top: 8px; }
        .about-badge-float {
            position: absolute; bottom: -20px; right: -20px;
            background: var(--gold); color: #000;
            border-radius: 16px; padding: 16px 20px;
            text-align: center; font-weight: 700;
            box-shadow: 0 8px 24px rgba(245,158,11,0.3);
        }
        .about-badge-float .num { font-size: clamp(1.4rem, 1.2rem + 0.8vw, 2rem); display: block; line-height: 1; }
        .about-badge-float .lbl { font-size: 0.75rem; }

        .about-text .section-title { text-align: left; }
        .about-text .section-sub { text-align: left; margin: 0 0 32px; }

        .about-points { list-style: none; padding: 0; margin: 0 0 32px; }
        .about-points li {
            display: flex; align-items: flex-start; gap: 12px;
            margin-bottom: 16px; color: var(--gray-700);
            font-size: clamp(0.85rem, 0.8rem + 0.2vw, 1.05rem);
        }
        .about-points li i {
            color: var(--blue-600); font-size: 1rem; margin-top: 3px; flex-shrink: 0;
        }

        /* ─── CONTACT SECTION ─────────────────── */
        #contact .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.4fr;
            gap: 48px; margin-top: 56px;
        }
        .contact-info-card {
            background: linear-gradient(135deg, var(--blue-900), var(--blue-700));
            border-radius: 24px; padding: var(--card-pad); color: #fff;
        }
        .contact-info-card h3 { font-size: clamp(1.1rem, 1rem + 0.5vw, 1.5rem); font-weight: 700; margin-bottom: 8px; }
        .contact-info-card p { color: rgba(255,255,255,0.7); margin-bottom: 32px; }
        .contact-item {
            display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px;
        }
        .contact-item-icon {
            width: 42px; height: 42px; background: rgba(255,255,255,0.12);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .contact-item-text strong { display: block; font-size: 0.8rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.5px; }
        .contact-item-text span { font-size: clamp(0.82rem, 0.8rem + 0.2vw, 1.05rem); }

        .contact-form-card {
            background: var(--white); border-radius: 24px; padding: var(--card-pad);
            border: 1px solid var(--gray-100);
            box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        }
        .contact-form-card h3 { font-size: clamp(1.1rem, 1rem + 0.4vw, 1.4rem); font-weight: 700; color: var(--gray-900); margin-bottom: 6px; }
        .contact-form-card p { color: var(--gray-500); font-size: clamp(0.8rem, 0.76rem + 0.15vw, 1rem); margin-bottom: 24px; }

        .form-field {
            margin-bottom: 18px;
        }
        .form-field label {
            display: block; font-size: clamp(0.72rem, 0.7rem + 0.15vw, 0.95rem); font-weight: 600;
            color: var(--gray-700); margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.4px;
        }
        .form-field input,
        .form-field textarea,
        .form-field select {
            width: 100%; padding: clamp(10px, 8px + 0.3vw, 15px);
            border: 1.5px solid var(--gray-300); border-radius: 10px;
            font-size: clamp(0.82rem, 0.8rem + 0.15vw, 1.05rem); color: var(--gray-700);
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--white);
            outline: none;
        }
        .form-field input:focus,
        .form-field textarea:focus { border-color: var(--blue-600); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-field textarea { resize: vertical; min-height: 120px; }

        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
            color: #fff; border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 700; cursor: pointer;
            transition: all 0.25s;
        }
        .btn-submit:hover { opacity: 0.88; transform: translateY(-2px); }

        /* ─── FOOTER ──────────────────────────── */
        .site-footer {
            background: var(--gray-900);
            padding: 60px 24px 32px;
            color: rgba(255,255,255,0.65);
        }
        .footer-inner { max-width: 1280px; margin: 0 auto; }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px; margin-bottom: 48px;
        }
        .footer-brand-name {
            font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 8px;
        }
        .footer-brand-sub { font-size: 0.8rem; color: rgba(255,255,255,0.5); }
        .footer-desc { font-size: 0.875rem; line-height: 1.8; margin-top: 16px; }
        .footer-col h4 {
            color: #fff; font-size: 0.88rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 18px;
        }
        .footer-col a {
            display: block; color: rgba(255,255,255,0.55);
            text-decoration: none; font-size: 0.875rem;
            margin-bottom: 10px; transition: color 0.2s;
        }
        .footer-col a:hover { color: var(--blue-400); }
        .footer-divider { border-color: rgba(255,255,255,0.1); margin-bottom: 24px; }
        .footer-bottom {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; font-size: 0.8rem;
        }
        .footer-bottom a { color: var(--blue-400); text-decoration: none; }

        /* ─── SCROLL TO TOP ───────────────────── */
        .scroll-top {
            position: fixed; bottom: 28px; right: 28px;
            width: 44px; height: 44px; border-radius: 12px;
            background: var(--blue-600); color: #fff;
            border: none; cursor: pointer; font-size: 1rem;
            display: none; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(37,99,235,0.35);
            transition: all 0.25s; z-index: 100;
        }
        .scroll-top.visible { display: flex; }
        .scroll-top:hover { transform: translateY(-3px); background: var(--blue-700); }

        /* ─── RESPONSIVE ──────────────────────── */
        /* Hide nav links on tablet — 7 links overflow at <1200px */
        @media (max-width: 1200px) {
            .nav-links { display: none !important; }
            .nav-hamburger { display: flex !important; }
        }
        @media (min-width: 1201px) {
            .nav-hamburger { display: none !important; }
        }

        @media (max-width: 991px) {
            .hero-grid { grid-template-columns: 1fr; gap: 48px; }
            .hero-visual { display: none; }
            #about .about-grid { grid-template-columns: 1fr; }
            .about-img-wrapper { max-width: 480px; margin: 0 auto; }
            #contact .contact-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
        }

        @media (max-width: 767px) {
            .site-topbar { display: none !important; }
            .mobile-menu { top: 68px !important; }
            .nav-links, .nav-login-group { display: none; }
            .nav-hamburger { display: flex; }
            .hero { padding: 100px 20px 60px !important; }
            .hero-stats { gap: 20px; flex-wrap: wrap; }
            .section { padding: 64px 20px; }
            .features-grid { grid-template-columns: 1fr; gap: 16px; }
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
            .footer-bottom { flex-direction: column; text-align: center; }
            .about-badge-float { position: static; display: inline-block; margin-top: 16px; }
        }

        @media (max-width: 480px) {
            .hero-title { font-size: 1.9rem; }
            .hero-cta { flex-direction: column; }
            .btn-hero-primary, .btn-hero-secondary { width: 100%; text-align: center; padding: 14px; }
        }
    </style>
</head>
<body>

<!-- ─── HEADER CONTAINER ─────────────────────────────────── -->
<div class="header-container" id="headerContainer">
    <!-- Blue Top Bar -->
    <div class="site-topbar">
        <div class="topbar-inner">
            <div class="topbar-left d-flex gap-3 align-items-center">
                <span><i class="fas fa-phone me-1"></i> <?= e($schoolPhone) ?></span>
                <span class="d-none d-sm-inline">|</span>
                <span><i class="fas fa-envelope me-1"></i> <?= e($schoolEmail) ?></span>
            </div>
            <div class="topbar-right d-flex gap-3 align-items-center">
                <a href="<?= BASE_URL ?>views/login.php?role=teacher" class="topbar-link"><i class="fas fa-chalkboard-teacher me-1"></i> Lecturer Login</a>
                <span>|</span>
                <a href="<?= BASE_URL ?>views/login.php" class="topbar-link"><i class="fas fa-shield-alt me-1"></i> Admin Login</a>
            </div>
        </div>
    </div>

    <!-- Main White Navbar -->
    <header class="site-navbar" id="siteNavbar">
        <div class="navbar-inner">
            <!-- Brand -->
            <a href="<?= BASE_URL ?>" class="nav-brand">
                <div class="nav-brand-icon">TI</div>
                <div class="nav-brand-text">
                    <span class="nav-brand-title">Talent Institute</span>
                    <span class="nav-brand-sub">معـهـد تالنت · Languages & Science</span>
                </div>
            </a>

            <!-- Desktop Nav Links (No Admission or Academics) -->
            <ul class="nav-links mb-0">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#features">Media</a></li>
                <li><a href="#about">Alumni</a></li>
                <li><a href="#home">Research</a></li>
                <li><a href="#home">Hemis</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>

            <!-- Right group (Search, Toggle Theme, Hamburger) -->
            <div class="d-flex align-items-center gap-2">
                <!-- Search Button -->
                <button class="btn p-0 d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:transparent;border:none;" aria-label="Search">
                    <i class="fas fa-search" style="color:var(--gray-700);font-size:1rem;"></i>
                </button>
                <!-- Theme Toggle -->
                <button id="themeToggleBtn" class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center me-2" style="width:38px;height:38px;border:1px solid var(--gray-300);background:transparent;" title="Toggle Dark/Light Mode">
                    <i class="fas fa-moon" id="themeIcon" style="color:var(--gray-500);font-size:0.9rem;"></i>
                </button>
                <!-- Hamburger (Only shown on mobile) -->
                <button class="nav-hamburger" id="hamburgerBtn" aria-label="Open menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
</div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
        <span style="font-weight: 700; color: var(--gray-900);">Menu Navigation</span>
        <!-- Mobile Theme Toggle -->
        <button id="themeToggleBtnMobile" class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:38px;height:38px;border:1px solid var(--gray-300);background:transparent;" title="Toggle Dark/Light Mode">
            <i class="fas fa-moon" id="themeIconMobile" style="color:var(--gray-500);font-size:0.9rem;"></i>
        </button>
    </div>
    <a href="#home" onclick="closeMobileMenu()">🏠 Home</a>
    <a href="#about" onclick="closeMobileMenu()">ℹ️ About Us</a>
    <a href="#features" onclick="closeMobileMenu()">📚 Media</a>
    <a href="#about" onclick="closeMobileMenu()">🎓 Alumni</a>
    <a href="#home" onclick="closeMobileMenu()">🔬 Research</a>
    <a href="#home" onclick="closeMobileMenu()">💻 Hemis</a>
    <a href="#contact" onclick="closeMobileMenu()">📞 Contact</a>
    <div class="mobile-login-btns">
        <a href="<?= BASE_URL ?>views/login.php?role=student" class="btn-student-m">
            <i class="fas fa-user-graduate me-2"></i> Student Login
        </a>
        <a href="<?= BASE_URL ?>views/login.php?role=teacher" class="btn-teacher-m">
            <i class="fas fa-chalkboard-teacher me-2"></i> Teacher Login
        </a>
        <a href="<?= BASE_URL ?>views/login.php" class="btn-student-m" style="background:#dbeafe; color:#1e3a8a;">
            <i class="fas fa-shield-alt me-2"></i> Admin Login
        </a>
    </div>
</div>


<!-- ─── HERO ─────────────────────────────────────────────── -->
<section class="hero" id="home">
    <div class="hero-circle hero-circle-1"></div>
    <div class="hero-circle hero-circle-2"></div>
    <div class="hero-circle hero-circle-3"></div>

    <div class="hero-content">
        <div class="hero-grid">
            <!-- Left Text -->
            <div>
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    Premier Educational Institution
                </div>
                <h1 class="hero-title">Talent Institute</h1>
                <p class="hero-title-arabic">معـهـد تـالـنـت للغات والمواد العلمية</p>
                <p class="hero-subtitle">
                    A world-class institute dedicated to Languages & Scientific Subjects.
                    We shape futures through excellence, discipline, and innovation.
                </p>
                <div class="hero-cta">
                    <a href="<?= BASE_URL ?>views/login.php?role=student" class="btn-hero-primary" id="hero-student-btn">
                        <i class="fas fa-sign-in-alt me-2"></i> Student Portal
                    </a>
                    <a href="#about" class="btn-hero-secondary">
                        <i class="fas fa-info-circle me-2"></i> Learn More
                    </a>
                </div>
                <div class="hero-stats">
                    <div>
                        <div class="hero-stat-num">500+</div>
                        <div class="hero-stat-label">Enrolled Students</div>
                    </div>
                    <div>
                        <div class="hero-stat-num">30+</div>
                        <div class="hero-stat-label">Expert Teachers</div>
                    </div>
                    <div>
                        <div class="hero-stat-num">10+</div>
                        <div class="hero-stat-label">Years of Excellence</div>
                    </div>
                </div>
            </div>

            <!-- Right Visual -->
            <div class="hero-visual">
                <div class="hero-card">
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon" style="background:rgba(96,165,250,0.15);">
                            <i class="fas fa-language" style="color:#60a5fa;"></i>
                        </div>
                        <div class="hero-feature-text">
                            <h5>Language Programs</h5>
                            <p>Arabic, English, Somali & more</p>
                        </div>
                    </div>
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon" style="background:rgba(52,211,153,0.15);">
                            <i class="fas fa-flask" style="color:#34d399;"></i>
                        </div>
                        <div class="hero-feature-text">
                            <h5>Scientific Subjects</h5>
                            <p>Math, Physics, Chemistry, Biology</p>
                        </div>
                    </div>
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon" style="background:rgba(251,191,36,0.15);">
                            <i class="fas fa-graduation-cap" style="color:#fbbf24;"></i>
                        </div>
                        <div class="hero-feature-text">
                            <h5>Digital Learning</h5>
                            <p>Modern smart management system</p>
                        </div>
                    </div>
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon" style="background:rgba(167,139,250,0.15);">
                            <i class="fas fa-users" style="color:#a78bfa;"></i>
                        </div>
                        <div class="hero-feature-text">
                            <h5>Expert Staff</h5>
                            <p>Qualified & passionate educators</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ─── FEATURES / PROGRAMS ───────────────────────────────── -->
<section class="section" id="features">
    <div class="section-inner text-center">
        <span class="section-badge">What We Offer</span>
        <h2 class="section-title">World-Class Programs & Services</h2>
        <p class="section-sub">Everything your child needs to excel — in one modern institution.</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-language"></i></div>
                <h3>Language Courses</h3>
                <p>Comprehensive programs in Arabic, English, Somali, and French taught by certified native speakers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calculator"></i></div>
                <h3>Mathematics</h3>
                <p>From foundational arithmetic to advanced calculus — structured for all age groups and skill levels.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-atom"></i></div>
                <h3>Sciences</h3>
                <p>Physics, Chemistry, and Biology with hands-on practical sessions and modern lab resources.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-laptop-code"></i></div>
                <h3>Computer Science</h3>
                <p>Programming, digital literacy, and IT fundamentals for the next generation of tech leaders.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Live Progress Tracking</h3>
                <p>Students and parents can access real-time grades, attendance, and performance reports online.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                <h3>Library & Resources</h3>
                <p>A rich library catalog with physical and digital resources available to all enrolled students.</p>
            </div>
        </div>
    </div>
</section>


<!-- ─── ABOUT ─────────────────────────────────────────────── -->
<section class="section section-alt" id="about">
    <div class="section-inner">
        <div class="about-grid">
            <div class="about-img-wrapper">
                <div class="about-img-main">
                    <i class="fas fa-university"></i>
                    <h2>Talent Institute</h2>
                    <p>معـهـد تالنت للتميز التعليمي</p>
                </div>
                <div class="about-badge-float">
                    <span class="num">10+</span>
                    <span class="lbl">Years of Excellence</span>
                </div>
            </div>
            <div class="about-text">
                <span class="section-badge">About Us</span>
                <h2 class="section-title">Shaping Futures Through Education</h2>
                <p class="section-sub">
                    Talent Institute was founded with a single mission — to deliver top-tier education
                    in languages and sciences to students of all ages in a nurturing, modern environment.
                </p>
                <ul class="about-points">
                    <li><i class="fas fa-check-circle"></i> <span>Experienced, certified teachers in every subject</span></li>
                    <li><i class="fas fa-check-circle"></i> <span>Small class sizes for personalized attention</span></li>
                    <li><i class="fas fa-check-circle"></i> <span>Modern digital school management system</span></li>
                    <li><i class="fas fa-check-circle"></i> <span>Transparent progress tracking for parents</span></li>
                    <li><i class="fas fa-check-circle"></i> <span>Safe, inclusive, and welcoming environment</span></li>
                    <li><i class="fas fa-check-circle"></i> <span>Flexible morning and evening class schedules</span></li>
                </ul>
                <a href="#contact" class="btn-hero-primary" style="display:inline-flex; align-items:center; gap:10px; background:linear-gradient(135deg,#1e3a8a,#2563eb); color:#fff; padding:13px 28px; border-radius:12px; text-decoration:none; font-weight:700;">
                    <i class="fas fa-envelope"></i> Get In Touch
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ─── CONTACT ───────────────────────────────────────────── -->
<section class="section" id="contact">
    <div class="section-inner">
        <div class="text-center mb-5">
            <span class="section-badge">Contact Us</span>
            <h2 class="section-title">We'd Love to Hear From You</h2>
            <p class="section-sub">Have questions about enrollment, programs, or fees? Reach out and we'll respond promptly.</p>
        </div>
        <div class="contact-grid">
            <!-- Info -->
            <div class="contact-info-card">
                <h3>Contact Information</h3>
                <p>Reach us through any of the channels below.</p>

                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="contact-item-text">
                        <strong>Address</strong>
                        <span>Mogadishu, Somalia</span>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fas fa-phone"></i></div>
                    <div class="contact-item-text">
                        <strong>Phone</strong>
                        <span><?= e($schoolPhone) ?></span>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fas fa-envelope"></i></div>
                    <div class="contact-item-text">
                        <strong>Email</strong>
                        <span><?= e($schoolEmail) ?></span>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fas fa-clock"></i></div>
                    <div class="contact-item-text">
                        <strong>Office Hours</strong>
                        <span>Sat–Thu · 7:00 AM – 6:00 PM</span>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="contact-form-card">
                <h3>Send a Message</h3>
                <p>Fill out the form below and we'll get back to you within 24 hours.</p>
                <form id="contactForm" onsubmit="handleContact(event)">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-field">
                            <label for="cf-name">Full Name</label>
                            <input type="text" id="cf-name" placeholder="Your full name" required>
                        </div>
                        <div class="form-field">
                            <label for="cf-phone">Phone Number</label>
                            <input type="tel" id="cf-phone" placeholder="+252 61...">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="cf-email">Email Address</label>
                        <input type="email" id="cf-email" placeholder="you@example.com">
                    </div>
                    <div class="form-field">
                        <label for="cf-subject">Subject</label>
                        <select id="cf-subject">
                            <option>General Inquiry</option>
                            <option>Student Enrollment</option>
                            <option>Fee Information</option>
                            <option>Programs & Schedules</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="cf-msg">Message</label>
                        <textarea id="cf-msg" placeholder="Write your message here..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane me-2"></i> Send Message
                    </button>
                </form>
                <div id="contactSuccess" style="display:none; margin-top:18px; padding:16px; background:#d1fae5; border-radius:10px; color:#065f46; font-weight:600; text-align:center;">
                    <i class="fas fa-check-circle me-2"></i> Thank you! Your message has been received.
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ─── FOOTER ────────────────────────────────────────────── -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-grid">
            <div>
                <div class="footer-brand-name">Talent Institute</div>
                <div class="footer-brand-sub">معـهـد تالنت للغات والمواد العلمية</div>
                <p class="footer-desc">
                    A premier institution dedicated to nurturing academic excellence through
                    quality education in Languages and Scientific Subjects.
                </p>
            </div>
            <div class="footer-col">
                <h4>Navigation</h4>
                <a href="#home">Home</a>
                <a href="#features">Programs</a>
                <a href="#about">About Us</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="footer-col">
                <h4>Portals</h4>
                <a href="<?= BASE_URL ?>views/login.php?role=student">Student Login</a>
                <a href="<?= BASE_URL ?>views/login.php?role=teacher">Teacher Login</a>
                <a href="<?= BASE_URL ?>views/login.php">Admin Login</a>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <a href="tel:<?= e($schoolPhone) ?>"><?= e($schoolPhone) ?></a>
                <a href="mailto:<?= e($schoolEmail) ?>"><?= e($schoolEmail) ?></a>
                <a href="#">Mogadishu, Somalia</a>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-bottom">
            <span>© 2026 Talent Institute. All rights reserved.</span>
            <span>Built with <i class="fas fa-heart" style="color:#ef4444;"></i> for education</span>
        </div>
    </div>
</footer>

<!-- Scroll To Top -->
<button class="scroll-top" id="scrollTopBtn" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
    // Theme Toggle Functionality
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        const iconDesktop = document.getElementById('themeIcon');
        const iconMobile = document.getElementById('themeIconMobile');

        if (theme === 'dark') {
            if (iconDesktop) { iconDesktop.className = 'fas fa-sun'; iconDesktop.style.color = '#fbbf24'; }
            if (iconMobile) { iconMobile.className = 'fas fa-sun'; iconMobile.style.color = '#fbbf24'; }
        } else {
            if (iconDesktop) { iconDesktop.className = 'fas fa-moon'; iconDesktop.style.color = '#64748b'; }
            if (iconMobile) { iconMobile.className = 'fas fa-moon'; iconMobile.style.color = '#64748b'; }
        }
    }

    // Load initial theme state
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Wire up toggle button clicks
    const toggleBtn = document.getElementById('themeToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    const toggleBtnMobile = document.getElementById('themeToggleBtnMobile');
    if (toggleBtnMobile) {
        toggleBtnMobile.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    // Navbar scroll effect
    const navbar = document.getElementById('siteNavbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 30);
        document.getElementById('scrollTopBtn').classList.toggle('visible', window.scrollY > 300);
    });

    // Hamburger
    const hamburger = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    hamburger.addEventListener('click', () => {
        mobileMenu.classList.toggle('open');
    });
    function closeMobileMenu() { mobileMenu.classList.remove('open'); }

    // Scroll to top
    document.getElementById('scrollTopBtn').addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Contact form
    function handleContact(e) {
        e.preventDefault();
        document.getElementById('contactSuccess').style.display = 'block';
        document.getElementById('contactForm').style.display = 'none';
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                closeMobileMenu();
                const offset = 110;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });
</script>
</body>
</html>
