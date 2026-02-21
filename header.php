<?php
// includes/header.php
$currentPage = $currentPage ?? '';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? APP_NAME) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* ===== DESIGN SYSTEM ===== */
    :root {
      --bg: #0d1117;
      --bg2: #161b22;
      --bg3: #21262d;
      --border: #30363d;
      --text: #e6edf3;
      --muted: #8b949e;
      --accent: #3b82f6;
      --accent-h: #2563eb;
      --green: #3fb950;
      --red: #f85149;
      --orange: #d29922;
      --purple: #a371f7;
      --radius: 8px;
      --shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
      --sidebar-width: 240px;
      --topbar-height: 52px;
    }

    /* Mode clair */
    :root[data-theme="light"] {
      --bg: #ffffff;
      --bg2: #f6f8fa;
      --bg3: #eaeef2;
      --border: #d0d7de;
      --text: #24292f;
      --muted: #57606a;
      --accent: #0969da;
      --accent-h: #033d8b;
      --green: #1a7f37;
      --red: #cf222e;
      --orange: #9e6a03;
      --purple: #8250df;
      --shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      font-size: 14px;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
    }

    /* SIDEBAR */
    .sidebar {
      width: 240px;
      min-height: 100vh;
      background: var(--bg2);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 100;
    }

    .sidebar-brand {
      padding: 20px 20px 16px;
      border-bottom: 1px solid var(--border);
    }

    .sidebar-brand .logo {
      font-size: 1.2rem;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .sidebar-brand .logo span {
      color: var(--accent);
    }

    .sidebar-brand .ver {
      font-size: 0.7rem;
      color: var(--muted);
      margin-top: 2px;
      font-family: 'JetBrains Mono', monospace;
    }

    .sidebar-nav {
      flex: 1;
      padding: 12px 8px;
      overflow-y: auto;
    }

    .nav-section {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--muted);
      padding: 16px 12px 6px;
      font-weight: 600;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: var(--radius);
      text-decoration: none;
      color: var(--muted);
      font-weight: 500;
      font-size: 0.875rem;
      transition: all 0.15s;
      margin-bottom: 2px;
    }

    .nav-link:hover,
    .nav-link.active {
      background: var(--bg3);
      color: var(--text);
    }

    .nav-link.active {
      color: var(--accent);
    }

    .nav-link .icon {
      font-size: 1rem;
      width: 20px;
      text-align: center;
      flex-shrink: 0;
    }

    /* SIDEBAR SESSION */
    .sidebar-session {
      padding: 12px 8px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-top: auto;
    }

    .session-user {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 1;
      min-width: 0;
    }

    .user-avatar {
      flex-shrink: 0;
      font-size: 1.8rem;
      color: var(--accent);
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg3);
      border-radius: 50%;
    }

    .user-info {
      flex: 1;
      min-width: 0;
    }

    .user-name {
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .user-status {
      font-size: 0.7rem;
      color: var(--green);
      font-weight: 500;
    }

    .session-logout {
      flex-shrink: 0;
      font-size: 0.9rem;
      color: var(--muted);
      padding: 8px 10px;
      border-radius: var(--radius);
      text-decoration: none;
      transition: all 0.15s;
      display: flex;
      align-items: center;
    }

    .session-logout:hover {
      background: var(--bg3);
      color: var(--red);
    }

    /* MAIN CONTENT */
    .main-wrap {
      margin-left: 240px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .topbar {
      background: var(--bg2);
      border-bottom: 1px solid var(--border);
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 50;
    }

    .topbar-title {
      font-size: 0.95rem;
      font-weight: 600;
    }

    .topbar-actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .sidebar-toggle {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      width: 36px;
      height: 36px;
      display: none;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.2s;
      padding: 0;
    }

    /* Overlay pour fermer la sidebar en mobile */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 998;
      backdrop-filter: blur(2px);
    }

    .sidebar-overlay.active {
      display: block;
    }

    .sidebar-toggle:hover {
      background: var(--bg3);
      border-color: var(--accent);
    }

    .theme-toggle {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.2s;
    }

    .theme-toggle:hover {
      background: var(--bg3);
      border-color: var(--accent);
    }

    .main-content {
      padding: 24px;
      flex: 1;
    }

    /* CARDS */
    .card {
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
    }

    .card-header {
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--bg3);
    }

    .card-title {
      font-weight: 600;
      font-size: 0.875rem;
    }

    .card-body {
      padding: 20px;
    }

    /* BUTTONS */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      border-radius: var(--radius);
      border: none;
      font-size: 0.8rem;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s;
      font-family: inherit;
      line-height: 1;
    }

    .btn-primary {
      background: var(--accent);
      color: white;
    }

    .btn-primary:hover {
      background: var(--accent-h);
    }

    .btn-success {
      background: var(--green);
      color: white;
    }

    .btn-danger {
      background: var(--red);
      color: white;
    }

    .btn-warning {
      background: var(--orange);
      color: white;
    }

    .btn-ghost {
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border);
    }

    .btn-ghost:hover {
      background: var(--bg3);
      color: var(--text);
    }

    .btn-sm {
      padding: 4px 10px;
      font-size: 0.75rem;
    }

    /* TABLES */
    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8rem;
    }

    th {
      background: var(--bg3);
      color: var(--muted);
      font-weight: 600;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 10px 14px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 11px 14px;
      border-bottom: 1px solid var(--border);
      color: var(--text);
      vertical-align: middle;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tbody tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    /* RESPONSIVE TABLE */
    @media (max-width: 479px) {
      .table-wrap {
        border: none;
      }

      table {
        display: block;
        width: 100%;
      }

      thead {
        display: none;
      }

      tbody {
        display: block;
        width: 100%;
      }

      tr {
        display: block;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 8px;
        background: var(--bg2);
      }

      td {
        display: block;
        text-align: right;
        padding: 8px 10px;
        border-bottom: 1px solid var(--border);
        position: relative;
        padding-left: 50%;
      }

      td:last-child {
        border-bottom: none;
      }

      td::before {
        content: attr(data-label);
        position: absolute;
        left: 8px;
        font-weight: 600;
        text-align: left;
        color: var(--muted);
        font-size: 0.7rem;
        text-transform: uppercase;
      }
    }

    /* RESPONSIVE MEDIA */
    img,
    video,
    iframe {
      max-width: 100%;
      height: auto;
      display: block;
    }

    /* RESPONSIVE TEXT */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      overflow-wrap: break-word;
      word-wrap: break-word;
      word-break: break-word;
    }

    /* MOBILE OPTIMIZATIONS */
    body {
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* Touch-friendly buttons on mobile */
    @media (max-width: 767px) {

      .btn,
      button {
        min-height: 44px;
        min-width: 44px;
      }

      a {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
      }
    }

    /* Improve readability on mobile */
    @media (max-width: 479px) {
      * {
        -webkit-tap-highlight-color: transparent;
      }

      input,
      select,
      textarea,
      button {
        font-size: 16px;
      }
    }

    /* FORMS */
    .form-group {
      margin-bottom: 16px;
    }

    label {
      display: block;
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    input,
    select,
    textarea {
      width: 100%;
      background: var(--bg3);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      padding: 8px 12px;
      font-size: 0.875rem;
      font-family: inherit;
      transition: border-color 0.15s;
      outline: none;
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    textarea {
      resize: vertical;
      min-height: 80px;
    }

    select option {
      background: var(--bg2);
    }

    /* BADGES */
    .badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 0.68rem;
      font-weight: 600;
      font-family: 'JetBrains Mono', monospace;
      letter-spacing: 0.02em;
    }

    .badge-primary {
      background: rgba(59, 130, 246, 0.15);
      color: #60a5fa;
    }

    .badge-success {
      background: rgba(63, 185, 80, 0.15);
      color: var(--green);
    }

    .badge-warning {
      background: rgba(210, 153, 34, 0.15);
      color: #fbbf24;
    }

    .badge-danger {
      background: rgba(248, 81, 73, 0.15);
      color: var(--red);
    }

    .badge-secondary {
      background: var(--bg3);
      color: var(--muted);
      border: 1px solid var(--border);
    }

    .badge-info {
      background: rgba(163, 113, 247, 0.15);
      color: var(--purple);
    }

    .badge-light {
      background: rgba(139, 148, 158, 0.1);
      color: var(--muted);
    }

    /* STAT CARDS */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 18px 20px;
    }

    .stat-label {
      font-size: 0.7rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 8px;
    }

    .stat-value {
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1;
    }

    .stat-value.green {
      color: var(--green);
    }

    .stat-value.red {
      color: var(--red);
    }

    .stat-value.blue {
      color: var(--accent);
    }

    .stat-value.orange {
      color: var(--orange);
    }

    .stat-sub {
      font-size: 0.72rem;
      color: var(--muted);
      margin-top: 6px;
    }

    /* ALERTS */
    .alert {
      padding: 12px 16px;
      border-radius: var(--radius);
      margin-bottom: 16px;
      font-size: 0.875rem;
      border: 1px solid;
    }

    .alert-success {
      background: rgba(63, 185, 80, 0.1);
      border-color: rgba(63, 185, 80, 0.3);
      color: var(--green);
    }

    .alert-danger {
      background: rgba(248, 81, 73, 0.1);
      border-color: rgba(248, 81, 73, 0.3);
      color: var(--red);
    }

    .alert-info {
      background: rgba(59, 130, 246, 0.1);
      border-color: rgba(59, 130, 246, 0.3);
      color: #60a5fa;
    }

    /* MISC */
    .flex {
      display: flex;
      align-items: center;
    }

    .gap-2 {
      gap: 8px;
    }

    .gap-3 {
      gap: 12px;
    }

    .text-muted {
      color: var(--muted);
    }

    .text-right {
      text-align: right;
    }

    .mono {
      font-family: 'JetBrains Mono', monospace;
    }

    .mb-4 {
      margin-bottom: 16px;
    }

    .mb-6 {
      margin-bottom: 24px;
    }

    /* GRID LAYOUT */
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 20px;
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }

    .empty-state .icon {
      font-size: 3rem;
      margin-bottom: 12px;
    }

    .empty-state p {
      font-size: 0.875rem;
    }

    /* PROGRESS */
    .progress-bar {
      height: 4px;
      background: var(--bg3);
      border-radius: 2px;
      overflow: hidden;
    }

    .progress-bar .fill {
      height: 100%;
      background: var(--accent);
      border-radius: 2px;
      transition: width 0.4s ease;
    }

    /* ===== RESPONSIVE DESIGN ===== */

    /* Mobile First - Extra Small (< 480px) */
    @media (max-width: 479px) {
      body {
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow-x: hidden;
      }

      .sidebar {
        width: 240px;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        height: 100vh;
        min-height: auto;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        flex-direction: column;
        align-items: stretch;
        border-right: 1px solid var(--border);
        border-bottom: none;
        z-index: 999;
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .sidebar-brand {
        padding: 20px 20px 16px;
        border-bottom: 1px solid var(--border);
        border-right: none;
        flex: none;
      }

      .sidebar-brand .logo {
        font-size: 1rem;
      }

      .sidebar-brand .ver {
        display: none;
      }

      .sidebar-nav {
        display: flex;
        flex-direction: column;
        flex: 1;
        overflow-y: auto;
      }

      .sidebar-session {
        padding: 10px 8px;
        border-top: 1px solid var(--border);
        flex: none;
      }

      .user-info {
        display: block;
      }

      .session-logout {
        padding: 8px 12px;
      }

      .sidebar-toggle {
        display: flex !important;
        visibility: visible !important;
        width: 36px !important;
        height: 36px !important;
        background: transparent !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius) !important;
        color: var(--text) !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        font-size: 1rem !important;
      }

      .main-wrap {
        margin-left: 0;
        width: 100%;
      }

      .main-content {
        padding: 16px;
      }

      .topbar {
        flex-direction: row;
        gap: 10px;
        align-items: center;
        padding: 10px 14px;
      }

      .topbar-title {
        flex: 1;
        font-size: 0.85rem;
      }

      .topbar-actions {
        justify-content: flex-end;
      }

      .stat-grid {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 16px;
      }

      .stat-card {
        padding: 12px 16px;
      }

      .stat-value {
        font-size: 1.3rem;
      }

      .grid-2,
      .grid-3 {
        grid-template-columns: 1fr;
        gap: 12px;
      }

      .card {
        border-radius: 8px;
      }

      .card-header {
        padding: 12px 16px;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }

      .card-body {
        padding: 12px 16px;
      }

      table {
        font-size: 0.7rem;
      }

      th {
        padding: 8px 10px;
        font-size: 0.6rem;
      }

      td {
        padding: 8px 10px;
      }

      .btn {
        padding: 6px 12px;
        font-size: 0.7rem;
      }

      .btn-sm {
        padding: 4px 8px;
        font-size: 0.65rem;
      }

      input,
      select,
      textarea {
        padding: 6px 10px;
        font-size: 0.8rem;
      }

      label {
        font-size: 0.7rem;
        margin-bottom: 4px;
      }

      .empty-state {
        padding: 40px 16px;
      }

      .empty-state .icon {
        font-size: 2.5rem;
        margin-bottom: 8px;
      }

      .empty-state p {
        font-size: 0.8rem;
      }

      .table-wrap {
        border-radius: 8px;
        border: 1px solid var(--border);
      }

      .flex {
        flex-wrap: wrap;
      }

      .gap-2 {
        gap: 6px;
      }

      .gap-3 {
        gap: 8px;
      }
    }

    /* Small (480px - 767px) - Hamburger Menu Active */
    @media (min-width: 480px) and (max-width: 767px) {
      .sidebar {
        width: 240px;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        height: 100vh;
        min-height: auto;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        flex-direction: column;
        align-items: stretch;
        border-right: 1px solid var(--border);
        border-bottom: none;
        z-index: 999;
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .sidebar-toggle {
        display: flex !important;
        visibility: visible !important;
        width: 36px !important;
        height: 36px !important;
        background: transparent !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius) !important;
        color: var(--text) !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        font-size: 1rem !important;
      }

      .sidebar-brand {
        padding: 20px 20px 16px;
        border-bottom: 1px solid var(--border);
        border-right: none;
        flex: none;
      }

      .sidebar-nav {
        display: flex;
        flex-direction: column;
        flex: 1;
        overflow-y: auto;
      }

      .user-info {
        display: block;
      }

      .main-wrap {
        margin-left: 0;
        width: 100%;
      }

      .main-content {
        padding: 16px;
      }

      .topbar {
        padding: 12px 16px;
        gap: 12px;
      }

      .stat-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }

      .grid-2,
      .grid-3 {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .table-wrap {
        overflow-x: auto;
      }

      table {
        font-size: 0.75rem;
      }

      input,
      select,
      textarea {
        padding: 7px 11px;
        font-size: 0.85rem;
      }

      .card-body {
        padding: 14px 16px;
      }

      .empty-state {
        padding: 50px 16px;
      }

      .sidebar-session {
        padding: 10px 8px;
        border-top: 1px solid var(--border);
        flex: none;
      }

      .session-logout {
        padding: 8px 10px;
      }
    }

    /* Medium (768px - 1023px) */
    @media (min-width: 768px) and (max-width: 1023px) {
      .sidebar {
        width: 200px;
      }

      .sidebar-brand .logo {
        font-size: 1rem;
      }

      .main-wrap {
        margin-left: 200px;
      }

      .main-content {
        padding: 18px;
      }

      .stat-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        margin-bottom: 18px;
      }

      .grid-2,
      .grid-3 {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .topbar {
        padding: 11px 18px;
      }

      .card-body {
        padding: 16px 18px;
      }

      table {
        font-size: 0.78rem;
      }

      .btn {
        padding: 7px 12px;
        font-size: 0.78rem;
      }

      .sidebar-toggle {
        display: none !important;
      }
    }

    /* Large Desktop (1024px - 1440px) */
    @media (min-width: 1024px) and (max-width: 1439px) {
      .sidebar {
        width: 220px;
      }

      .main-wrap {
        margin-left: 220px;
      }

      .stat-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
      }

      .grid-2 {
        grid-template-columns: 1fr 1fr;
      }

      .grid-3 {
        grid-template-columns: repeat(3, 1fr);
      }

      .sidebar-toggle {
        display: none !important;
      }
    }

    /* Extra Large Desktop (> 1440px) */
    @media (min-width: 1440px) {
      .sidebar {
        width: 240px;
      }

      .main-wrap {
        margin-left: 240px;
      }

      .main-content {
        padding: 32px 40px;
      }

      .stat-grid {
        grid-template-columns: repeat(6, 1fr);
        gap: 20px;
      }

      .grid-2 {
        grid-template-columns: 1fr 1fr;
      }

      .grid-3 {
        grid-template-columns: repeat(3, 1fr);
      }

      .sidebar-toggle {
        display: none !important;
      }
    }

    /* FONT AWESOME ICONS */
    i.fa-solid,
    i.fas {
      display: inline-block;
      font-variant-numeric: tabular-nums;
      margin-right: 6px;
      vertical-align: -0.125em;
    }

    .card-title i.fa-solid,
    .card-title i.fas,
    .stat-label i.fa-solid,
    .stat-label i.fas,
    .empty-state .icon i.fa-solid,
    .empty-state .icon i.fas {
      margin-right: 8px;
      vertical-align: -0.125em;
    }

    button i.fa-solid,
    button i.fas,
    a i.fa-solid,
    a i.fas {
      margin-right: 6px;
      vertical-align: -0.05em;
    }

    .nav-link i.fa-solid,
    .nav-link i.fas {
      margin-right: 10px;
      width: 18px;
      text-align: center;
      vertical-align: -0.125em;
    }
  </style>
</head>

<body>

  <!-- Overlay mobile pour fermer le sidebar -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <nav class="sidebar">
    <div class="sidebar-brand">
      <div class="logo">Next<span>mux</span></div>
    </div>
    <div class="sidebar-nav">
      <div class="nav-section">Vue d'ensemble</div>
      <a href="index.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-chart-bar"></i></span> Tableau de bord
      </a>

      <div class="nav-section">Opérations</div>
      <a href="clients.php" class="nav-link <?= $currentPage === 'clients' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-users"></i></span> Clients
      </a>
      <a href="projets.php" class="nav-link <?= $currentPage === 'projets' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-folder"></i></span> Projets
      </a>
      <a href="taches.php" class="nav-link <?= $currentPage === 'taches' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-check-circle"></i></span> Tâches
      </a>

      <div class="nav-section">Finance</div>
      <a href="factures.php" class="nav-link <?= $currentPage === 'factures' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-receipt"></i></span> Factures
      </a>
      <a href="paiements.php" class="nav-link <?= $currentPage === 'paiements' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-credit-card"></i></span> Paiements
      </a>
      <a href="depenses.php" class="nav-link <?= $currentPage === 'depenses' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-money-bill"></i></span> Dépenses
      </a>
      <a href="finance.php" class="nav-link <?= $currentPage === 'finance' ? 'active' : '' ?>">
        <span class="icon"><i class="fa-solid fa-chart-line"></i></span> Suivi financier
      </a>
    </div>

    <!-- Session/Login Section -->
    <div class="sidebar-session">
      <div class="session-user">
        <div class="user-avatar"><i class="fa-solid fa-user-circle"></i></div>
        <div class="user-info">
          <div class="user-name"><?= h($_SESSION['user']['nom'] ?? 'Utilisateur') ?></div>
          <div class="user-status"><?= h($_SESSION['user']['email'] ?? '') ?></div>
        </div>
      </div>
      <a href="logout.php" class="session-logout" title="Se déconnecter"
         onclick="return confirm('Se déconnecter ?')"><i class="fa-solid fa-sign-out-alt"></i></a>
    </div>
  </nav>

  <div class="main-wrap">
    <div class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle" title="Menu navigation">
        <i class="fa-solid fa-bars"></i>
      </button>
      <span class="topbar-title"><?= h($pageTitle ?? APP_NAME) ?></span>
      <div class="topbar-actions">
        <button class="theme-toggle" id="themeToggle" title="Basculer mode sombre/clair">🌙</button>
        <span class="text-muted"><?= date('d/m/Y') ?></span>
      </div>
    </div>

    <div class="main-content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
          <?= h($flash['message']) ?>
        </div>
      <?php endif; ?>