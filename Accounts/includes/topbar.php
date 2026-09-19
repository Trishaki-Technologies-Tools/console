        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div>
                        <h1 id="page-title"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></h1>
                    </div>
                </div>
                <div class="user-info" style="display: flex; align-items: center; gap: 15px;">
                    <div class="fy-capsule-wrapper">
                        <svg class="fy-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <select id="financialYear" class="fy-capsule-select" onchange="changeFinancialYear(this.value)">
                            <?php
                            $startYear = 2025; // System begins from FY 2025-2026, nothing before it
                            $currentYear = intval(date('Y'));
                            $currentMonth = intval(date('n'));
                            
                            // Financial year starts on April 1st
                            $calcFyStart = ($currentMonth >= 4) ? $currentYear : $currentYear - 1;
                            $currentFy = $calcFyStart . '-' . ($calcFyStart + 1);

                            // Preserve existing selected cookie, default to current FY
                            $selectedFy = isset($_COOKIE['financial_year']) && !empty($_COOKIE['financial_year']) ? $_COOKIE['financial_year'] : $currentFy;
                            
                            // Prevent any legacy pre-2025 selection
                            if (preg_match('/^(\d{4})-(\d{4})$/', $selectedFy, $m) && intval($m[1]) < $startYear) {
                                $selectedFy = $currentFy;
                                setcookie('financial_year', $selectedFy, time() + 86400 * 30, '/');
                                $_COOKIE['financial_year'] = $selectedFy;
                            } elseif (!isset($_COOKIE['financial_year'])) {
                                setcookie('financial_year', $currentFy, time() + 86400 * 30, '/');
                                $_COOKIE['financial_year'] = $currentFy;
                            }

                            // Automatically adds each new financial year on April 1st
                            $maxYear = max($calcFyStart, 2025);
                            for ($y = $startYear; $y <= $maxYear; $y++) {
                                $nextYear = $y + 1;
                                $value = $y . '-' . $nextYear;
                                $selected = ($value == $selectedFy) ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>FY $value</option>";
                            }
                            ?>
                        </select>
                        <svg class="fy-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
            </header>

            <div class="content" id="content-area">