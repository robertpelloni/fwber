<?php
// scripts/apply_migration_web.php
// Purpose: Safely apply the legacy matcher compatibility migration via browser
// Guards: Requires user login and DEBUG_MODE=true in environment

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/_init.php'; // initializes $pdo and $securityManager

// Require login
if (!validateSessionOrCookiesReturnLoggedIn()) {
    http_response_code(403);
    echo 'Forbidden: login required';
    exit;
}

// Require DEBUG_MODE enabled
$debug = getenv('DEBUG_MODE') ?: ($_ENV['DEBUG_MODE'] ?? '');
if (!in_array(strtolower((string)$debug), ['1','true','yes'], true)) {
    http_response_code(403);
    echo 'Forbidden: DEBUG_MODE must be enabled to run migrations via web.';
    exit;
}

header('Content-Type: text/plain');

function hasColumn(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    if (!hasColumn($pdo, $table, $column)) {
        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
        $pdo->exec($sql);
        echo "Added column {$table}.{$column}\n";
    } else {
        echo "Column exists {$table}.{$column}\n";
    }
}

// 1) Try to run the SQL migration file (MySQL 8+)
$migrationPath = $root . '/db/migrations/2025-10-11-legacy-matcher-compat.sql';
$appliedViaFile = false;
if (file_exists($migrationPath)) {
    $sql = file_get_contents($migrationPath);
    if ($sql !== false) {
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n|;\s*$/m', $sql)));
        try {
            $pdo->beginTransaction();
            foreach ($statements as $stmt) {
                $s = trim($stmt);
                if ($s === '' || str_starts_with($s, '--') || str_starts_with($s, '/*')) {
                    continue;
                }
                $pdo->exec($s);
            }
            $pdo->commit();
            $appliedViaFile = true;
            echo "Applied migration file successfully.\n";
        } catch (Throwable $e) {
            $pdo->rollBack();
            echo "Migration file apply failed (will fallback to column-by-column): " . $e->getMessage() . "\n";
        }
    }
}

// 2) Column-by-column fallback (compatible with MySQL < 8)
if (!$appliedViaFile) {
    echo "Running fallback column ensures...\n";
    $pdo->beginTransaction();
    try {
        // Core legacy gates
        ensureColumn($pdo, 'users', 'distance', "varchar(16) DEFAULT 'dist50m'");
        ensureColumn($pdo, 'users', 'profileDone', 'TINYINT(1) DEFAULT 1');
        ensureColumn($pdo, 'users', 'verified', 'TINYINT(1) DEFAULT 1');

        // Helper to add many TINYINT flags
        $boolCols = [
            // Gender preferences
            'b_wantGenderMan','b_wantGenderWoman','b_wantGenderTSWoman','b_wantGenderTSMan','b_wantGenderCDWoman','b_wantGenderCDMan','b_wantGenderCoupleMF','b_wantGenderCoupleMM','b_wantGenderCoupleFF','b_wantGenderGroup',
            // Body types
            'b_wantBodyTiny','b_wantBodySlim','b_wantBodyAverage','b_wantBodyMuscular','b_wantBodyCurvy','b_wantBodyThick','b_wantBodyBBW',
            // Ethnicity
            'b_wantEthnicityWhite','b_wantEthnicityAsian','b_wantEthnicityLatino','b_wantEthnicityIndian','b_wantEthnicityBlack','b_wantEthnicityOther',
            // Hair color
            'b_wantHairColorLight','b_wantHairColorMedium','b_wantHairColorDark','b_wantHairColorRed','b_wantHairColorGray','b_wantHairColorOther',
            // Hair length
            'b_wantHairLengthBald','b_wantHairLengthShort','b_wantHairLengthMedium','b_wantHairLengthLong',
            // Tattoos
            'b_wantTattoosNone','b_wantTattoosSome','b_wantTattoosAllOver',
            // Looks
            'b_wantLooksUgly','b_wantLooksPlain','b_wantLooksQuirky','b_wantLooksAverage','b_wantLooksAttractive','b_wantLooksHottie','b_wantLooksSuperModel',
            // Intelligence
            'b_wantIntelligenceGoodHands','b_wantIntelligenceBitSlow','b_wantIntelligenceAverage','b_wantIntelligenceFaster','b_wantIntelligenceGenius',
            // Bedroom personality
            'b_wantBedroomPersonalityPassive','b_wantBedroomPersonalityShy','b_wantBedroomPersonalityConfident','b_wantBedroomPersonalityAggressive',
            // Pubic hair
            'b_wantPubicHairShaved','b_wantPubicHairTrimmed','b_wantPubicHairCropped','b_wantPubicHairNatural','b_wantPubicHairHairy',
            // Penis size
            'b_wantPenisSizeTiny','b_wantPenisSizeSkinny','b_wantPenisSizeAverage','b_wantPenisSizeThick','b_wantPenisSizeHuge',
            // Body hair
            'b_wantBodyHairSmooth','b_wantBodyHairAverage','b_wantBodyHairHairy',
            // Breast size
            'b_wantBreastSizeTiny','b_wantBreastSizeSmall','b_wantBreastSizeAverage','b_wantBreastSizeLarge','b_wantBreastSizeHuge',
            // Lifestyle & health
            'b_smokeCigarettes','b_noCigs','b_lightDrinker','b_noLightDrink','b_heavyDrinker','b_noHeavyDrink','b_smokeMarijuana','b_noMarijuana','b_psychedelics','b_noPsychedelics','b_otherDrugs','b_noDrugs','b_haveWarts','b_noWarts','b_haveHerpes','b_noHerpes','b_haveHepatitis','b_noHepatitis','b_haveOtherSTI','b_noOtherSTIs','b_haveHIV','b_noHIV','b_marriedTheyKnow','b_noMarriedTheyKnow','b_marriedSecret','b_noMarriedSecret','b_poly','b_noPoly','b_disability','b_noDisabled',
            // Sexual activities
            'b_wantSafeSex','b_wantBarebackSex','b_wantOralGive','b_wantOralReceive','b_wantAnalTop','b_wantAnalBottom','b_wantFilming','b_wantVoyeur','b_wantExhibitionist','b_wantRoleplay','b_wantSpanking','b_wantDom','b_wantSub','b_wantStrapon','b_wantCuckold','b_wantFurry',
            // Meeting preferences
            'b_whereMyPlace','b_whereYouHost','b_whereCarDate','b_whereHotelIPay','b_whereHotelYouPay','b_whereHotelSplit','b_whereBarClub','b_whereGymSauna','b_whereNudeBeach','b_whereOther',
        ];
        foreach ($boolCols as $col) {
            ensureColumn($pdo, 'users', $col, 'TINYINT(1) DEFAULT 0');
        }

        $pdo->commit();
        echo "Fallback column ensures complete.\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo 'Fallback migration failed: ' . $e->getMessage();
        exit;
    }
}

echo "\nDone.";
