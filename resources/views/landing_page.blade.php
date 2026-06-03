<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Download UNNATI - The ultimate premium family wealth tracking and financial productivity application. Monitor your net worth, manage assets, liabilities, and track tasks all in one secure space.">
    <meta name="keywords"
        content="Unnati app, StatelyWorld Unnati, wealth tracking, net worth manager, family budget, task manager, financial productivity">
    <meta name="author" content="StatelyWorld">
    <title>UNNATI | Premium Family Wealth & Financial Productivity App</title>

    <!-- Google Fonts: Outfit for Headings, Inter for Body -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- FontAwesome for Premium Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* CSS variables for sleek, tailored HSL color system */
        :root {
            --bg-primary: #020617;
            --bg-secondary: #0b0f19;
            --bg-card: rgba(15, 23, 42, 0.45);
            --border-glow: rgba(0, 129, 221, 0.15);
            --border-glow-active: rgba(0, 129, 221, 0.4);

            --color-primary: #0081DD;
            /* Stately Brand Blue */
            --color-primary-rgb: 0, 129, 221;
            --color-secondary: #EF7F1A;
            /* Stately Brand Orange */
            --color-secondary-rgb: 239, 127, 26;
            --color-danger: #e11d48;
            /* Rose Red */
            --color-danger-rgb: 225, 29, 72;

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-headings: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;

            --card-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.7);
            --glow-shadow: 0 0 40px -10px rgba(0, 129, 221, 0.25);
        }

        /* Modern resets & base styling */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            font-family: var(--font-body);
            line-height: 1.6;
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(0, 129, 221, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(239, 127, 26, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(225, 29, 72, 0.03) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* Glassmorphism Floating Navbar */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        header.scrolled {
            padding: 12px 0;
            background: rgba(2, 6, 23, 0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.5);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: var(--font-headings);
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
            color: #fff;
            font-size: 20px;
            position: relative;
            overflow: hidden;
        }

        .logo-icon::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: logo-shimmer 4s infinite linear;
        }

        @keyframes logo-shimmer {
            0% {
                transform: translate(-50%, -50%) rotate(45deg);
            }

            100% {
                transform: translate(50%, 50%) rotate(45deg);
            }
        }

        .logo-text span {
            background: linear-gradient(135deg, #fff 40%, var(--color-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
            padding: 8px 0;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .nav-links a:hover {
            color: var(--text-main);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Ultra Premium Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 14px;
            font-family: var(--font-headings);
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            border: none;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-main);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), #005DA0);
            color: #fff;
            box-shadow: 0 4px 20px rgba(0, 129, 221, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 129, 221, 0.5);
        }

        .btn-download {
            background: linear-gradient(135deg, var(--color-secondary), #C7620C);
            color: #fff;
            box-shadow: 0 4px 20px rgba(239, 127, 26, 0.3);
            font-size: 16px;
            padding: 16px 36px;
            border-radius: 16px;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(239, 127, 26, 0.5);
        }

        /* Hero Section */
        .hero {
            padding: 160px 0 100px;
            position: relative;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 60px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 100px;
            color: var(--color-primary);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hero-badge i {
            font-size: 11px;
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.3;
            }

            50% {
                opacity: 1;
            }
        }

        .hero-title {
            font-family: var(--font-headings);
            font-size: 56px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 24px;
        }

        .hero-title span.grad-1 {
            background: linear-gradient(135deg, #fff, var(--color-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-title span.grad-2 {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 580px;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 48px;
        }

        .hero-stats {
            display: flex;
            align-items: center;
            gap: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 32px;
        }

        .hero-stat-item h3 {
            font-family: var(--font-headings);
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
        }

        .hero-stat-item p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Futuristic 3D Smartphone Mockup */
        .mockup-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            perspective: 1000px;
        }

        .mockup-glow {
            position: absolute;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
            filter: blur(40px);
            z-index: 1;
            pointer-events: none;
        }

        .phone-mockup {
            width: 310px;
            height: 630px;
            background: #090d16;
            border: 11px solid #1e293b;
            border-radius: 44px;
            box-shadow:
                0px 25px 50px -12px rgba(0, 0, 0, 0.8),
                0px 0px 0px 4px rgba(255, 255, 255, 0.05),
                var(--glow-shadow);
            position: relative;
            z-index: 2;
            overflow: hidden;
            transition: transform 0.5s ease;
            transform: rotateY(-15deg) rotateX(10deg);
        }

        .phone-mockup:hover {
            transform: rotateY(-5deg) rotateX(5deg) translateY(-8px);
        }

        /* Phone Screen Details */
        .phone-speaker {
            width: 70px;
            height: 4px;
            background: #1e293b;
            border-radius: 10px;
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .phone-camera {
            width: 9px;
            height: 9px;
            background: #0f172a;
            border-radius: 50%;
            position: absolute;
            top: 4px;
            left: 36%;
            z-index: 10;
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: #020617;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .phone-screen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
        }

        /* Features Section */
        .section-title-wrapper {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }

        .section-tag {
            font-family: var(--font-headings);
            font-size: 13px;
            font-weight: 700;
            color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            display: block;
        }

        .section-title {
            font-family: var(--font-headings);
            font-size: 40px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }

        .section-desc {
            color: var(--text-muted);
            font-size: 16px;
        }

        .features {
            padding: 100px 0;
            position: relative;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 28px;
            padding: 40px 32px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(800px circle at var(--x, 0px) var(--y, 0px), rgba(56, 189, 248, 0.06), transparent 40%);
            pointer-events: none;
            z-index: 1;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--border-glow-active);
            box-shadow: 0 20px 40px -15px rgba(56, 189, 248, 0.15);
        }

        .feature-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 28px;
            position: relative;
            z-index: 2;
        }

        .icon-blue {
            background: rgba(56, 189, 248, 0.1);
            color: var(--color-primary);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .icon-green {
            background: rgba(16, 185, 129, 0.1);
            color: var(--color-secondary);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .icon-red {
            background: rgba(225, 29, 72, 0.1);
            color: var(--color-danger);
            border: 1px solid rgba(225, 29, 72, 0.2);
        }

        .feature-card h3 {
            font-family: var(--font-headings);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 14px;
            position: relative;
            z-index: 2;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        /* Premium Dashboard Showcase */
        .showcase {
            padding: 100px 0;
            background: rgba(11, 15, 25, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            position: relative;
        }

        .showcase-grid {
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: 80px;
            align-items: center;
        }

        .showcase-content h2 {
            font-family: var(--font-headings);
            font-size: 38px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 24px;
            letter-spacing: -1px;
        }

        .showcase-content p {
            color: var(--text-muted);
            font-size: 16px;
            margin-bottom: 32px;
        }

        .showcase-feature-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .showcase-item {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .showcase-check {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--color-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .showcase-item-text h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .showcase-item-text p {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 0;
        }

        /* Glassmorphism Financial widget preview */
        .preview-pane {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 32px;
            padding: 32px;
            box-shadow: var(--card-shadow);
        }

        .pane-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .pane-badge {
            padding: 4px 12px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--color-secondary);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .chart-svg {
            width: 100%;
            height: 200px;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: draw 4s forwards cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes draw {
            to {
                stroke-dashoffset: 0;
            }
        }

        .pane-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 24px;
        }

        .pane-stat-item h5 {
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .pane-stat-item p {
            font-family: var(--font-headings);
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }

        /* Step-by-Step Installation Guide */
        .guide {
            padding: 100px 0;
            position: relative;
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .step-card {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 32px;
            position: relative;
            transition: border-color 0.3s ease;
        }

        .step-card:hover {
            border-color: rgba(56, 189, 248, 0.3);
        }

        .step-number {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-headings);
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 24px;
        }

        .step-card h3 {
            font-family: var(--font-headings);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .step-card p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Promoted CTA Section */
        .cta-section {
            padding: 120px 0;
            position: relative;
        }

        .cta-box {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(9, 13, 22, 0.9) 100%);
            border: 1px solid var(--border-glow);
            border-radius: 40px;
            padding: 80px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow:
                0px 30px 60px -15px rgba(0, 0, 0, 0.8),
                0 0 50px rgba(56, 189, 248, 0.1);
        }

        .cta-box::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 60%);
            top: -250px;
            right: -250px;
            pointer-events: none;
        }

        .cta-box::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.1) 0%, transparent 60%);
            bottom: -250px;
            left: -250px;
            pointer-events: none;
        }

        .cta-title {
            font-family: var(--font-headings);
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #fff 50%, var(--color-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cta-desc {
            color: var(--text-muted);
            font-size: 18px;
            max-width: 650px;
            margin: 0 auto 40px;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .cta-meta {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 32px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .cta-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cta-meta-item i {
            color: var(--color-secondary);
        }

        /* Footer */
        footer {
            background-color: #010409;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 60px 0 30px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo-desc p {
            margin-top: 16px;
            line-height: 1.6;
            max-width: 320px;
        }

        .footer-column h4 {
            font-family: var(--font-headings);
            color: var(--text-main);
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-column ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-column ul a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-column ul a:hover {
            color: var(--color-primary);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .social-links {
            display: flex;
            gap: 16px;
        }

        .social-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: var(--color-primary);
            color: #fff;
            transform: translateY(-3px);
        }

        /* Responsive Breakpoints */
        @media (max-width: 991px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 50px;
                text-align: center;
            }

            .hero-badge {
                margin: 0 auto 24px;
            }

            .hero-title {
                font-size: 44px;
            }

            .hero-subtitle {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-cta {
                justify-content: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .showcase-grid {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 575px) {
            .nav-links {
                display: none;
                /* Simplifies mobile presentation */
            }

            .hero-title {
                font-size: 34px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
                width: 100%;
            }

            .cta-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Floating Glassmorphism Navbar -->
    <header id="main-header">
        <div class="container navbar-content">
            <a href="#" class="logo" id="nav-logo">
                <img src="/images/logo.png" alt="UNNATI" style="height: 38px; width: auto; border-radius: 8px;">
                <div class="logo-text"><span>UNNATI</span></div>
            </a>

            <ul class="nav-links">
                <li><a href="#features" id="link-features">Features</a></li>
                <li><a href="#showcase" id="link-showcase">Showcase</a></li>
                <li><a href="#guide" id="link-guide">How to Install</a></li>
                <li><a href="#download" id="link-download">Download</a></li>
            </ul>

            <div class="nav-actions">
                <a href="{{ route('login') }}" class="btn btn-outline" id="btn-navbar-signin">Sign In</a>
            </div>
        </div>
    </header>

    <!-- Main Hero Section -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-circle-play"></i> Promoting UNNATI v1.0
                </div>
                <h1 class="hero-title" id="main-hero-title">
                    <span class="grad-1">Accelerate Your</span><br>
                    <span class="grad-2">Family Wealth & Productivity</span>
                </h1>
                <p class="hero-subtitle">
                    UNNATI brings your assets, savings, liabilities, and household tasks into a unified, secure, and
                    ultra-premium collaborative platform. Work together with your family to grow your net worth.
                </p>
                <div class="hero-cta">
                    <a href="https://swapi.statelyworld.com/storage/app-prod-release.apk"
                        class="btn btn-primary btn-download" id="btn-hero-download">
                        <i class="fa-solid fa-arrow-down-to-line"></i> Download APK for Android
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline" id="btn-hero-explore">
                        Go to Web Dashboard <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <h3 id="stat-downloads">10k+</h3>
                        <p>Downloads</p>
                    </div>
                    <div class="hero-stat-item">
                        <h3 id="stat-security">256-bit</h3>
                        <p>Encryption</p>
                    </div>
                    <div class="hero-stat-item">
                        <h3 id="stat-rating">4.9/5</h3>
                        <p>User Rating</p>
                    </div>
                </div>
            </div>

            <!-- Smartphone Showcase Mockup -->
            <div class="mockup-container">
                <div class="mockup-glow"></div>
                <div class="phone-mockup" id="device-mockup">
                    <div class="phone-speaker"></div>
                    <div class="phone-camera"></div>
                    <div class="phone-screen">
                        <img src="/images/unnati_app_dashboard_mockup.png" alt="UNNATI Application Dashboard UI Mockup">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Grid -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title-wrapper">
                <span class="section-tag">Powerful Modules</span>
                <h2 class="section-title" id="features-section-title">Designed for Modern Families</h2>
                <p class="section-desc">Experience a comprehensive set of financial and productivity modules
                    specifically crafted to push your progress and organization forwards.</p>
            </div>

            <div class="features-grid">
                <!-- Asset Card -->
                <div class="feature-card" id="card-assets">
                    <div class="feature-icon-wrapper icon-green">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h3>Total Assets Tracking</h3>
                    <p>Log checking accounts, savings, cash balances, and other investments. See real-time totals
                        beautifully aggregated in your home dashboard.</p>
                </div>

                <!-- Liability Card -->
                <div class="feature-card" id="card-liabilities">
                    <div class="feature-icon-wrapper icon-red">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <h3>Liability & Debt Monitor</h3>
                    <p>Track credit card limits, current debts, and outstanding loans. Color-coded warning layers keep
                        you aware of balances before they gain interest.</p>
                </div>

                <!-- Family Card -->
                <div class="feature-card" id="card-family">
                    <div class="feature-icon-wrapper icon-blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h3>Family Collaboration</h3>
                    <p>Manage shared household goals. Enable dedicated individual user views or switch instantly to an
                        aggregated "All Family" dashboard overview.</p>
                </div>

                <!-- Task Card -->
                <div class="feature-card" id="card-tasks">
                    <div class="feature-icon-wrapper icon-blue">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <h3>Unified Task Manager</h3>
                    <p>Tackle household chores and goals together. Combine tasks alongside your financial operations to
                        stay productive in daily routines.</p>
                </div>

                <!-- PDF Card -->
                <div class="feature-card" id="card-pdf">
                    <div class="feature-icon-wrapper icon-green">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <h3>Detailed PDF Statements</h3>
                    <p>Filter your transactions by user, account, or date, and generate beautifully formatted,
                        high-fidelity PDF statements with a single tap.</p>
                </div>

                <!-- Security Card -->
                <div class="feature-card" id="card-security">
                    <div class="feature-icon-wrapper icon-red">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <h3>State-of-the-art Security</h3>
                    <p>Unlock securely using fingerprint authentication (Local Auth). Enjoy data confidentiality with
                        fully encrypted local and database storage.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Showcase and Interactive Section -->
    <section class="showcase" id="showcase">
        <div class="container showcase-grid">
            <div class="showcase-content">
                <span class="section-tag">Visual Performance</span>
                <h2 id="showcase-title">Visual insights that make your wealth obvious</h2>
                <p>No more digging through complex spreadsheets. UNNATI analyzes your accounts and creates automatic
                    growth curves, categorizations, and comparisons to give you instant clarity.</p>

                <div class="showcase-feature-list">
                    <div class="showcase-item">
                        <div class="showcase-check"><i class="fa-solid fa-check"></i></div>
                        <div class="showcase-item-text">
                            <h4>Dynamic Chart Analysis</h4>
                            <p>Beautiful curves mapping your income, expenses, and savings automatically over the last 6
                                months.</p>
                        </div>
                    </div>
                    <div class="showcase-item">
                        <div class="showcase-check"><i class="fa-solid fa-check"></i></div>
                        <div class="showcase-item-text">
                            <h4>Automatic Category Tagging</h4>
                            <p>Clean visualization breakdown showing exactly where your budget goes, automatically
                                grouped by tags.</p>
                        </div>
                    </div>
                    <div class="showcase-item">
                        <div class="showcase-check"><i class="fa-solid fa-check"></i></div>
                        <div class="showcase-item-text">
                            <h4>Net Worth Gauge</h4>
                            <p>Assets minus liabilities mapped on a central status tracker to keep your growing wealth
                                front and center.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="preview-pane" id="financial-preview-pane">
                <div class="pane-header">
                    <h4>AGGREGATED WEALTH INDEX</h4>
                    <span class="pane-badge">Active Insights</span>
                </div>

                <!-- Interactive premium SVG financial chart -->
                <svg class="chart-svg" viewBox="0 0 500 200">
                    <defs>
                        <linearGradient id="chart-grad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="var(--color-primary)" stop-opacity="0.3" />
                            <stop offset="100%" stop-color="var(--color-primary)" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <!-- Background Grid -->
                    <line x1="0" y1="40" x2="500" y2="40" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="90" x2="500" y2="90" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="140" x2="500" y2="140" stroke="rgba(255,255,255,0.03)" stroke-width="1" />

                    <!-- Graph Path Fill -->
                    <path d="M 0,200 L 0,160 Q 100,120 180,140 T 360,70 T 500,40 L 500,200 Z" fill="url(#chart-grad)" />

                    <!-- Graph Line -->
                    <path d="M 0,160 Q 100,120 180,140 T 360,70 T 500,40" fill="none" stroke="var(--color-primary)"
                        stroke-width="4" stroke-linecap="round" />

                    <!-- Glowing points -->
                    <circle cx="180" cy="140" r="6" fill="var(--color-secondary)" />
                    <circle cx="360" cy="70" r="6" fill="var(--color-primary)" />
                    <circle cx="500" cy="40" r="8" fill="#fff" />
                </svg>

                <div class="pane-stats">
                    <div class="pane-stat-item">
                        <h5>Net Worth</h5>
                        <p style="color: var(--color-primary);">₹ 12.80L</p>
                    </div>
                    <div class="pane-stat-item">
                        <h5>Assets</h5>
                        <p style="color: var(--color-secondary);">₹ 15.20L</p>
                    </div>
                    <div class="pane-stat-item">
                        <h5>Liabilities</h5>
                        <p style="color: var(--color-danger);">₹ 2.40L</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Step-by-Step Installation Guide -->
    <section class="guide" id="guide">
        <div class="container">
            <div class="section-title-wrapper">
                <span class="section-tag">Getting Started</span>
                <h2>How to Install the APK on Android</h2>
                <p class="section-desc">Follow these quick, easy steps to download and safely run the UNNATI app on
                    your Android device.</p>
            </div>

            <div class="steps-container">
                <div class="step-card" id="step-one">
                    <div class="step-number">01</div>
                    <h3>Download the APK</h3>
                    <p>Click any of the download buttons to fetch the verified, safe production APK file directly to
                        your smartphone.</p>
                </div>
                <div class="step-card" id="step-two">
                    <div class="step-number">02</div>
                    <h3>Enable Safe Source</h3>
                    <p>When prompted by your browser, tap Settings and toggle "Allow from this source" so your system
                        can open the installer.</p>
                </div>
                <div class="step-card" id="step-three">
                    <div class="step-number">03</div>
                    <h3>Tap Install</h3>
                    <p>Open your downloads, tap on the UNNATI APK file, and click Install. Setup takes less than 30
                        seconds!</p>
                </div>
                <div class="step-card" id="step-four">
                    <div class="step-number">04</div>
                    <h3>Log In & Track</h3>
                    <p>Open the app, sign in using your StatelyWorld credentials, and start building financial harmony
                        together.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Promotional Call to Action Box -->
    <section class="cta-section" id="download">
        <div class="container">
            <div class="cta-box" id="cta-box-wrapper">
                <h2 class="cta-title" id="cta-main-title">Ready to Take Control?</h2>
                <p class="cta-desc">Download UNNATI now and experience a new, modern, and highly rewarding way of
                    keeping your family wealth and daily productivity fully organized.</p>

                <div class="cta-buttons">
                    <a href="https://swapi.statelyworld.com/storage/app-prod-release.apk"
                        class="btn btn-primary btn-download" id="btn-cta-download">
                        <i class="fa-solid fa-arrow-down-to-line"></i> Download APK (v1.0.0)
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline"
                        style="padding: 16px 36px; border-radius: 16px;" id="btn-cta-explore">
                        Launch Web Version <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                <div class="cta-meta">
                    <div class="cta-meta-item">
                        <i class="fa-solid fa-shield-halved"></i> 100% Secure & Ad-free
                    </div>
                    <div class="cta-meta-item">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Direct APK Release
                    </div>
                    <div class="cta-meta-item">
                        <i class="fa-solid fa-bolt"></i> Compact Size (~18MB)
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Professional Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-logo-desc">
                    <a href="#" class="logo">
                        <img src="/images/logo.png" alt="UNNATI Logo"
                            style="height: 38px; width: auto; border-radius: 8px;">
                        <div class="logo-text"><span>UNNATI</span></div>
                    </a>
                    <p>Accelerating family wealth tracking and daily productivity in one ultra-secure, gorgeous space.
                    </p>
                </div>

                <div class="footer-column">
                    <h4>Application</h4>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#showcase">UI Showcase</a></li>
                        <li><a href="#guide">Installation Guide</a></li>
                        <li><a href="https://swapi.statelyworld.com/storage/app-prod-release.apk">Download APK</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Web Dashboard</h4>
                    <ul>
                        <li><a href="{{ route('login') }}">Access Account</a></li>
                        <li><a href="/register">Create Account</a></li>
                        <li><a href="/dashboard">View Dashboard</a></li>
                        <li><a href="/profile">User Profile</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="https://statelyworld.com">StatelyWorld</a></li>
                        <li><a href="https://statelyworld.com/privacy-policy-unnati/">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Support Portal</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p id="copyright-text">&copy; 2026 StatelyWorld. All Rights Reserved. Crafted for premium productivity.
                </p>
                <div class="social-links">
                    <a href="#" class="social-icon" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="social-icon" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" class="social-icon" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interaction and Animations Script -->
    <script>
        // Adjust Header Style on Scroll
        const header = document.getElementById('main-header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Spotlight Hover Effect on Feature Cards
        const cards = document.querySelectorAll('.feature-card');
        cards.forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--x', `${x}px`);
                card.style.setProperty('--y', `${y}px`);
            });
        });
    </script>
</body>

</html>