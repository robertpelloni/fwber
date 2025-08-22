<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('fwber_users')->onDelete('cascade');
            
            // Age preferences
            $table->integer('want_age_from')->default(18);
            $table->integer('want_age_to')->default(99);
            
            // Gender preferences
            $table->boolean('want_gender_man')->default(false);
            $table->boolean('want_gender_woman')->default(false);
            $table->boolean('want_gender_ts_woman')->default(false);
            $table->boolean('want_gender_ts_man')->default(false);
            $table->boolean('want_gender_cd_woman')->default(false);
            $table->boolean('want_gender_cd_man')->default(false);
            $table->boolean('want_gender_couple_mf')->default(false);
            $table->boolean('want_gender_couple_mm')->default(false);
            $table->boolean('want_gender_couple_ff')->default(false);
            $table->boolean('want_gender_group')->default(false);
            
            // Physical preferences
            $table->boolean('want_body_tiny')->default(false);
            $table->boolean('want_body_slim')->default(false);
            $table->boolean('want_body_average')->default(false);
            $table->boolean('want_body_muscular')->default(false);
            $table->boolean('want_body_curvy')->default(false);
            $table->boolean('want_body_thick')->default(false);
            $table->boolean('want_body_bbw')->default(false);
            
            $table->boolean('want_ethnicity_white')->default(false);
            $table->boolean('want_ethnicity_asian')->default(false);
            $table->boolean('want_ethnicity_latino')->default(false);
            $table->boolean('want_ethnicity_indian')->default(false);
            $table->boolean('want_ethnicity_black')->default(false);
            $table->boolean('want_ethnicity_other')->default(false);
            
            $table->boolean('want_hair_color_light')->default(false);
            $table->boolean('want_hair_color_medium')->default(false);
            $table->boolean('want_hair_color_dark')->default(false);
            $table->boolean('want_hair_color_red')->default(false);
            $table->boolean('want_hair_color_gray')->default(false);
            $table->boolean('want_hair_color_other')->default(false);
            
            $table->boolean('want_hair_length_bald')->default(false);
            $table->boolean('want_hair_length_short')->default(false);
            $table->boolean('want_hair_length_medium')->default(false);
            $table->boolean('want_hair_length_long')->default(false);
            
            $table->boolean('want_tattoos_none')->default(false);
            $table->boolean('want_tattoos_some')->default(false);
            $table->boolean('want_tattoos_all_over')->default(false);
            
            $table->boolean('want_looks_ugly')->default(false);
            $table->boolean('want_looks_plain')->default(false);
            $table->boolean('want_looks_quirks')->default(false);
            $table->boolean('want_looks_average')->default(false);
            $table->boolean('want_looks_attractive')->default(false);
            $table->boolean('want_looks_hottie')->default(false);
            $table->boolean('want_looks_supermodel')->default(false);
            
            $table->boolean('want_intelligence_slow')->default(false);
            $table->boolean('want_intelligence_bit_slow')->default(false);
            $table->boolean('want_intelligence_average')->default(false);
            $table->boolean('want_intelligence_faster')->default(false);
            $table->boolean('want_intelligence_genius')->default(false);
            
            $table->boolean('want_bedroom_personality_passive')->default(false);
            $table->boolean('want_bedroom_personality_shy')->default(false);
            $table->boolean('want_bedroom_personality_confident')->default(false);
            $table->boolean('want_bedroom_personality_aggressive')->default(false);
            
            $table->boolean('want_pubic_hair_shaved')->default(false);
            $table->boolean('want_pubic_hair_trimmed')->default(false);
            $table->boolean('want_pubic_hair_average')->default(false);
            $table->boolean('want_pubic_hair_natural')->default(false);
            $table->boolean('want_pubic_hair_hairy')->default(false);
            
            $table->boolean('want_penis_size_tiny')->default(false);
            $table->boolean('want_penis_size_skinny')->default(false);
            $table->boolean('want_penis_size_average')->default(false);
            $table->boolean('want_penis_size_thick')->default(false);
            $table->boolean('want_penis_size_huge')->default(false);
            
            $table->boolean('want_body_hair_smooth')->default(false);
            $table->boolean('want_body_hair_average')->default(false);
            $table->boolean('want_body_hair_hairy')->default(false);
            
            $table->boolean('want_breast_size_tiny')->default(false);
            $table->boolean('want_breast_size_small')->default(false);
            $table->boolean('want_breast_size_average')->default(false);
            $table->boolean('want_breast_size_large')->default(false);
            $table->boolean('want_breast_size_huge')->default(false);
            
            // Sexual preferences
            $table->boolean('want_safe_sex')->default(false);
            $table->boolean('want_bareback_sex')->default(false);
            $table->boolean('want_oral_give')->default(false);
            $table->boolean('want_oral_receive')->default(false);
            $table->boolean('want_anal_top')->default(false);
            $table->boolean('want_anal_bottom')->default(false);
            $table->boolean('want_filming')->default(false);
            $table->boolean('want_voyeur')->default(false);
            $table->boolean('want_exhibitionist')->default(false);
            $table->boolean('want_roleplay')->default(false);
            $table->boolean('want_spanking')->default(false);
            $table->boolean('want_dom')->default(false);
            $table->boolean('want_sub')->default(false);
            $table->boolean('want_strapon')->default(false);
            $table->boolean('want_cuckold')->default(false);
            $table->boolean('want_furry')->default(false);
            
            // Venue preferences
            $table->boolean('where_my_place')->default(false);
            $table->boolean('where_you_host')->default(false);
            $table->boolean('where_car_date')->default(false);
            $table->boolean('where_hotel_i_pay')->default(false);
            $table->boolean('where_hotel_you_pay')->default(false);
            $table->boolean('where_hotel_split')->default(false);
            $table->boolean('where_bar_club')->default(false);
            $table->boolean('where_gym_sauna')->default(false);
            $table->boolean('where_nude_beach')->default(false);
            $table->boolean('where_other')->default(false);
            
            // Relationship preferences
            $table->boolean('mutual_long_term')->default(false);
            $table->boolean('mutual_no_strings')->default(false);
            $table->boolean('mutual_wear_mask')->default(false);
            $table->boolean('mutual_only_play_safe')->default(false);
            $table->boolean('mutual_proof_of_testing')->default(false);
            
            // Deal breakers
            $table->boolean('no_cigs')->default(false);
            $table->boolean('no_light_drink')->default(false);
            $table->boolean('no_heavy_drink')->default(false);
            $table->boolean('no_marijuana')->default(false);
            $table->boolean('no_psychedelics')->default(false);
            $table->boolean('no_drugs')->default(false);
            $table->boolean('no_herpes')->default(false);
            $table->boolean('no_warts')->default(false);
            $table->boolean('no_hepatitis')->default(false);
            $table->boolean('no_other_stis')->default(false);
            $table->boolean('no_hiv')->default(false);
            $table->boolean('no_married_they_know')->default(false);
            $table->boolean('no_married_secret')->default(false);
            $table->boolean('no_poly')->default(false);
            $table->boolean('no_disabled')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
