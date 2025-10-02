# Profile Field Mapping - Legacy to Modern

## Field Categories

### 1. Core Demographics (users table)
- `username` - Display name
- `age` - Calculated from birthday or direct input
- `gender` - male|female|mtf|ftm|cdmtf|cdftm|mf|mm|ff|group
- `body` - tiny|slim|average|muscular|curvy|thick|bbw
- `ethnicity` - white|asian|latino|indian|black|other
- `hairColor` - light|medium|dark|red|gray|other
- `hairLength` - bald|short|medium|long
- `tattoos` - none|some|allOver
- `overallLooks` - ugly|plain|quirky|average|attractive|hottie|superModel
- `intelligence` - goodHands|bitSlow|average|faster|genius
- `bedroomPersonality` - passive|shy|confident|aggressive

### 2. Physical Attributes (gender-specific)
- `pubicHair` - shaved|trimmed|cropped|natural|hairy
- `penisSize` - tiny|skinny|average|thick|huge (male-presenting)
- `bodyHair` - smooth|average|hairy (male-presenting)
- `breastSize` - tiny|small|average|large|huge (female-presenting)

### 3. Location & Distance
- `lat`, `lon` - GPS coordinates
- `distance` - Legacy format: dist0m|dist5m|dist10m|dist20m|dist50m
- `max_distance` - Modern format: 5|10|20|50 (miles)
- `city`, `state`, `country`, `zip_code`

### 4. Age Preferences
- `wantAgeFrom` - Minimum age seeking
- `wantAgeTo` - Maximum age seeking

### 5. Gender Preferences (b_* flags)
- `b_wantGenderMan` - Seeking men
- `b_wantGenderWoman` - Seeking women
- `b_wantGenderTSWoman` - Seeking trans women
- `b_wantGenderTSMan` - Seeking trans men
- `b_wantGenderCDWoman` - Seeking crossdresser women
- `b_wantGenderCDMan` - Seeking crossdresser men
- `b_wantGenderCoupleMF` - Seeking MF couples
- `b_wantGenderCoupleMM` - Seeking MM couples
- `b_wantGenderCoupleFF` - Seeking FF couples
- `b_wantGenderGroup` - Seeking groups

### 6. Physical Preferences (b_* flags)
- `b_wantBodyTiny`, `b_wantBodySlim`, `b_wantBodyAverage`, `b_wantBodyMuscular`, `b_wantBodyCurvy`, `b_wantBodyThick`, `b_wantBodyBBW`
- `b_wantEthnicityWhite`, `b_wantEthnicityAsian`, `b_wantEthnicityLatino`, `b_wantEthnicityIndian`, `b_wantEthnicityBlack`, `b_wantEthnicityOther`
- `b_wantHairColorLight`, `b_wantHairColorMedium`, `b_wantHairColorDark`, `b_wantHairColorRed`, `b_wantHairColorGray`, `b_wantHairColorOther`
- `b_wantHairLengthBald`, `b_wantHairLengthShort`, `b_wantHairLengthMedium`, `b_wantHairLengthLong`
- `b_wantTattoosNone`, `b_wantTattoosSome`, `b_wantTattoosAllOver`
- `b_wantLooksUgly`, `b_wantLooksPlain`, `b_wantLooksQuirks`, `b_wantLooksAverage`, `b_wantLooksAttractive`, `b_wantLooksHottie`, `b_wantLooksSuperModel`
- `b_wantIntelligenceSlow`, `b_wantIntelligenceBitSlow`, `b_wantIntelligenceAverage`, `b_wantIntelligenceFaster`, `b_wantIntelligenceGenius`
- `b_wantBedroomPersonalityPassive`, `b_wantBedroomPersonalityShy`, `b_wantBedroomPersonalityConfident`, `b_wantBedroomPersonalityAggressive`

### 7. Physical Attribute Preferences (b_* flags)
- `b_wantPubicHairShaved`, `b_wantPubicHairTrimmed`, `b_wantPubicHairAverage`, `b_wantPubicHairNatural`, `b_wantPubicHairHairy`
- `b_wantPenisSizeTiny`, `b_wantPenisSizeSkinny`, `b_wantPenisSizeAverage`, `b_wantPenisSizeThick`, `b_wantPenisSizeHuge`
- `b_wantBodyHairSmooth`, `b_wantBodyHairAverage`, `b_wantBodyHairHairy`
- `b_wantBreastSizeTiny`, `b_wantBreastSizeSmall`, `b_wantBreastSizeAverage`, `b_wantBreastSizeLarge`, `b_wantBreastSizeHuge`

### 8. Lifestyle & Health (b_* flags)
- `b_smokeCigarettes`, `b_noCigs`
- `b_lightDrinker`, `b_noLightDrink`
- `b_heavyDrinker`, `b_noHeavyDrink`
- `b_smokeMarijuana`, `b_noMarijuana`
- `b_psychedelics`, `b_noPsychedelics`
- `b_otherDrugs`, `b_noDrugs`
- `b_haveWarts`, `b_noWarts`
- `b_haveHerpes`, `b_noHerpes`
- `b_haveHepatitis`, `b_noHepatitis`
- `b_haveOtherSTI`, `b_noOtherSTIs`
- `b_haveHIV`, `b_noHIV`
- `b_marriedTheyKnow`, `b_noMarriedTheyKnow`
- `b_marriedSecret`, `b_noMarriedSecret`
- `b_poly`, `b_noPoly`
- `b_disability`, `b_noDisabled`

### 9. Sexual Activities (b_* flags - paired for matching)
- `b_wantSafeSex`, `b_wantBarebackSex`
- `b_wantOralGive`, `b_wantOralReceive`
- `b_wantAnalTop`, `b_wantAnalBottom`
- `b_wantFilming`
- `b_wantVoyeur`, `b_wantExhibitionist`
- `b_wantRoleplay`
- `b_wantSpanking`
- `b_wantDom`, `b_wantSub`
- `b_wantStrapon`
- `b_wantCuckold`
- `b_wantFurry`

### 10. Meeting Preferences (b_* flags)
- `b_whereMyPlace`, `b_whereYouHost`
- `b_whereCarDate`
- `b_whereHotelIPay`, `b_whereHotelYouPay`, `b_whereHotelSplit`
- `b_whereBarClub`
- `b_whereGymSauna`
- `b_whereNudeBeach`
- `b_whereOther`

## Form Implementation Notes

1. **Current form fields** (profile-form.php):
   - username, age, gender ✓
   - country, zip_code, max_distance ✓
   - wantGenderMan, wantGenderWoman, wantGenderNonBinary (needs mapping to b_* flags)

2. **Missing critical fields**:
   - All physical attributes (body, ethnicity, hair, tattoos, etc.)
   - All preference checkboxes (b_* flags)
   - Age preferences (wantAgeFrom, wantAgeTo)
   - Lifestyle/health flags
   - Sexual activity preferences
   - Meeting location preferences

3. **Data flow**:
   - Form POST → edit-profile.php → ProfileManager->saveProfile()
   - ProfileManager splits fields between users table and user_preferences table
   - getMatches() reads from both tables for matching logic

4. **Validation needed**:
   - Age range validation (18+)
   - Gender-specific fields (penisSize for male-presenting, breastSize for female-presenting)
   - At least one gender preference selected
   - At least one activity preference selected
