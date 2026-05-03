import { Router } from "express";
import prisma from "../lib/prisma.js";
import { authenticate } from "../middleware/auth.js";

const router = Router();

// ─── Zodiac calculation ─────────────────────────────────────────────────────
function getZodiacSign(month: number, day: number): string {
	if ((month === 1 && day >= 20) || (month === 2 && day <= 18))
		return "Aquarius";
	if ((month === 2 && day >= 19) || (month === 3 && day <= 20)) return "Pisces";
	if ((month === 3 && day >= 21) || (month === 4 && day <= 19)) return "Aries";
	if ((month === 4 && day >= 20) || (month === 5 && day <= 20)) return "Taurus";
	if ((month === 5 && day >= 21) || (month === 6 && day <= 20)) return "Gemini";
	if ((month === 6 && day >= 21) || (month === 7 && day <= 22)) return "Cancer";
	if ((month === 7 && day >= 23) || (month === 8 && day <= 22)) return "Leo";
	if ((month === 8 && day >= 23) || (month === 9 && day <= 22)) return "Virgo";
	if ((month === 9 && day >= 23) || (month === 10 && day <= 22)) return "Libra";
	if ((month === 10 && day >= 23) || (month === 11 && day <= 21))
		return "Scorpio";
	if ((month === 11 && day >= 22) || (month === 12 && day <= 21))
		return "Sagittarius";
	return "Capricorn";
}

// GET /api/profile - Get current user's profile
router.get("/", authenticate, async (req: any, res) => {
	try {
		const userId = BigInt(req.user.id);
		const user = await prisma.users.findUnique({ where: { id: userId } });
		const profile = await prisma.user_profiles.findFirst({
			where: { user_id: userId },
		});
		const photos = await prisma.photos.findMany({
			where: { user_id: userId, is_private: false },
			orderBy: { is_primary: "desc" },
		});
		if (!profile) {
			res.json({
				id: Number(userId),
				email: user?.email || "",
				email_verified: !!user?.email_verified_at,
				created_at: user?.created_at?.toISOString() || "",
				last_online: user?.last_seen_at?.toISOString() || "",
				profile: null,
				photos,
			});
			return;
		}
		// Fetch established relationship links
		const relationshipLinks = await prisma.relationship_links.findMany({
			where: { user_id: userId, is_confirmed: true },
			include: {
				users_relationship_links_related_user_idTousers: {
					select: {
						id: true,
						name: true,
						user_profiles: {
							select: {
								display_name: true,
								avatar_url: true,
							},
						},
					},
				},
			},
		});

		const p = serialize(profile);
		// Parse JSON fields back to objects
		for (const col of [
			"preferences",
			"looking_for",
			"interests",
			"languages",
			"social_media",
			"sti_status",
			"fetishes",
			"interested_in",
		]) {
			p[col] = parseJsonField(p[col]);
		}

		// Build the nested structure the frontend expects
		const prefs = p.preferences || {};
		const completeness = await calculateProfileCompletenessSummary(userId, profile);
		res.json({
			id: Number(userId),
			email: user?.email || "",
			email_verified: !!user?.email_verified_at,
			created_at: user?.created_at?.toISOString() || "",
			last_online: user?.last_seen_at?.toISOString() || "",
			profile: {
				display_name: p.display_name,
				bio: p.bio,
				date_of_birth: p.date_of_birth || p.birthdate,
				age:
					p.date_of_birth || p.birthdate
						? Math.floor(
								(Date.now() -
									new Date(p.date_of_birth || p.birthdate).getTime()) /
									(365.25 * 24 * 60 * 60 * 1000),
							)
						: null,
				gender: p.gender,
				pronouns: p.pronouns,
				sexual_orientation: p.sexual_orientation,
				relationship_style: p.relationship_style,
				looking_for: p.looking_for || [],
				interests: p.interests || [],
				location: {
					latitude: p.location_latitude ? Number(p.location_latitude) : null,
					longitude: p.location_longitude ? Number(p.location_longitude) : null,
					max_distance: parseInt(p.location_name) || 25,
					city: p.location_description
						? p.location_description.split(",")[0]?.trim() || ""
						: "",
					state: p.location_description
						? p.location_description.split(",")[1]?.trim() || ""
						: "",
					match_scope: prefs.match_scope || "local",
					search_country: prefs.search_country || "",
					search_city: prefs.search_city || "",
				},
				love_language: p.love_language,
				personality_type: p.personality_type,
				chronotype: p.chronotype || p.sleep_schedule,
				social_media: p.social_media || {},
				communication_style: prefs.communication_style,
				blood_type: p.blood_type,
				sti_status: p.sti_status || {},
				family_plans: p.family_plans,
				relationship_goals: p.relationship_goals,
				languages: p.languages || [],
				zodiac_sign: (() => {
					if (p.zodiac_sign) return p.zodiac_sign;
					const dob = p.date_of_birth || p.birthdate;
					if (!dob) return null;
					const d = new Date(dob);
					return getZodiacSign(d.getMonth() + 1, d.getDate());
				})(),
				drinking_status: p.drinking_status,
				smoking_status: p.smoking_status,
				cannabis_status: p.cannabis_status,
				dietary_preferences: p.dietary_preferences,
				exercise_habits: prefs.exercise,
				sleep_habits: p.sleep_schedule,
				pets: p.has_pets ? ["yes"] : [],
				children: p.has_children ? "yes" : "",
				religion: p.religion,
				political_views: p.political_views,
				voice_intro_url: p.voice_intro_url,
				is_federated: p.is_federated,
				is_travel_mode: p.is_travel_mode,
				travel_latitude: p.travel_latitude ? Number(p.travel_latitude) : null,
				travel_longitude: p.travel_longitude
					? Number(p.travel_longitude)
					: null,
				travel_location_name: p.travel_location_name,
				height_cm: p.height_cm,
				body_type: p.body_type,
				hair_color: p.hair_color,
				eye_color: p.eye_color,
				skin_tone: p.skin_tone,
				facial_hair: p.facial_hair,
				fitness_level: p.fitness_level,
				clothing_style: p.clothing_style,
				dominant_hand: p.dominant_hand,
				ethnicity: p.ethnicity,
				occupation: p.occupation || prefs.occupation,
				education: p.education,
				tattoos: p.tattoos === "true" || p.tattoos === true,
				piercings: p.piercings === "true" || p.piercings === true,
				breast_size: p.breast_size || null,
				penis_length_cm: p.penis_length_cm || null,
				penis_girth_cm: p.penis_girth_cm || null,
				fetishes: p.fetishes || [],
				preferences: prefs,
				profile_complete: completeness.required_complete,
				completion_percentage: completeness.percentage,
				completion_percentage_required: completeness.percentage, // for consistency
				completion_status: completeness,
				photos: photos.map((ph: any) => ({
					id: Number(ph.id),
					url: ph.file_path || "",
					is_private: ph.is_private || false,
					is_primary: ph.is_primary || false,
				})),
			},
		});
	} catch (error: any) {
		console.error("[GET /api/profile]", error.message);
		res.json({});
	}
});

// POST /api/profile - Create/update profile
router.post("/", authenticate, async (req: any, res) => {
	// Reuse PUT logic — same sanitization, column filtering, type coercion
	try {
		const userId = BigInt(req.user.id);
		const raw = req.body;

		const data: any = {};
		for (const [key, val] of Object.entries(raw)) {
			if (val === undefined || val === null) continue;
			if (key === "location") {
				const loc = val as any;
				if (loc.latitude != null) data.location_latitude = Number(loc.latitude);
				if (loc.longitude != null)
					data.location_longitude = Number(loc.longitude);
				if (loc.city || loc.state)
					data.location_description = [loc.city, loc.state]
						.filter(Boolean)
						.join(", ");
				if (loc.max_distance != null)
					data.location_name = String(loc.max_distance);
				// Store match_scope, search_country, search_city in preferences
				if (loc.match_scope || loc.search_country || loc.search_city) {
					const existingPrefs =
						typeof raw.preferences === "object" ? raw.preferences : {};
					const mergedPrefs = { ...existingPrefs };
					if (loc.match_scope) mergedPrefs.match_scope = loc.match_scope;
					if (loc.search_country)
						mergedPrefs.search_country = loc.search_country;
					if (loc.search_city) mergedPrefs.search_city = loc.search_city;
				}
				continue;
			}
			if (key === "travel_location") {
				const loc = val as any;
				if (loc.latitude != null) data.travel_latitude = Number(loc.latitude);
				if (loc.longitude != null)
					data.travel_longitude = Number(loc.longitude);
				if (loc.name != null) data.travel_location_name = loc.name;
				continue;
			}
			if (key === "voice_intro" || key === "voice_intro_url") continue;
			if (key === "looking_for") {
				data[key] = typeof val === "string" ? val : JSON.stringify(val);
				continue;
			}
			if (!PROFILE_COLUMNS.has(key)) continue;
			if (JSON_COLUMNS.has(key)) {
				data[key] = typeof val === "string" ? val : JSON.stringify(val);
			} else {
				data[key] = val;
			}
		}

		// Sync both date columns so reads always work
		if (data.birthdate && !data.date_of_birth)
			data.date_of_birth = data.birthdate;
		if (data.date_of_birth && !data.birthdate)
			data.birthdate = data.date_of_birth;
		if (
			data.birthdate &&
			typeof data.birthdate === "string" &&
			data.birthdate.length === 10
		)
			data.birthdate = new Date(data.birthdate + "T00:00:00.000Z");
		if (
			data.date_of_birth &&
			typeof data.date_of_birth === "string" &&
			data.date_of_birth.length === 10
		)
			data.date_of_birth = new Date(data.date_of_birth + "T00:00:00.000Z");

		delete data.id;
		delete data.user_id;
		delete data.created_at;
		delete data.updated_at;

		const STRING_BOOL_COLUMNS = ["tattoos", "piercings"];
		for (const col of STRING_BOOL_COLUMNS) {
			if (col in data && typeof data[col] === "boolean")
				data[col] = data[col] ? "true" : "false";
		}
		const BOOL_COLUMNS = ["has_children", "wants_children", "has_pets"];
		for (const col of BOOL_COLUMNS) {
			if (col in data && typeof data[col] === "string")
				data[col] = data[col] === "true" || data[col] === "1";
		}

		const existing = await prisma.user_profiles.findFirst({
			where: { user_id: userId },
		});
		if (existing) {
			const updated = await prisma.user_profiles.update({
				where: { id: existing.id },
				data,
			});
			res.json(serialize(updated));
		} else {
			const created = await prisma.user_profiles.create({
				data: { user_id: userId, ...data },
			});
			res.json(serialize(created));
		}
	} catch (error: any) {
		console.error("[POST /api/profile]", error.message);
		res
			.status(500)
			.json({ message: error.message || "Failed to update profile" });
	}
});

// Helper: recursively convert BigInt to Number for JSON serialization
function serialize(obj: any): any {
	if (obj === null || obj === undefined) return obj;
	if (typeof obj === "bigint") return Number(obj);
	if (obj instanceof Date) return obj;
	if (Array.isArray(obj)) return obj.map((v: any) => serialize(v));
	if (typeof obj === "object") {
		const out: any = {};
		for (const key of Object.keys(obj)) {
			out[key] = serialize(obj[key]);
		}
		return out;
	}
	return obj;
}

// Helper: safely parse JSON fields that may already be objects or strings
function parseJsonField(val: any): any {
	if (!val) return val;
	if (typeof val === "object") return val;
	if (typeof val === "string") {
		try {
			return JSON.parse(val);
		} catch {
			return val;
		}
	}
	return val;
}

function getEmptyCompletenessSummary() {
	return {
		percentage: 0,
		required_complete: false,
		missing_required: ["profile"],
		missing_optional: [],
		sections: {
			basic: false,
			location: false,
			preferences: false,
			interests: false,
			physical: false,
			lifestyle: false,
		},
	};
}

async function calculateProfileCompletenessSummary(userId: bigint, profile: any) {
	if (!profile) {
		return getEmptyCompletenessSummary();
	}

	const photoCount = await prisma.photos.count({
		where: { user_id: userId, is_private: false },
	});
	const interestsArr = Array.isArray(parseJsonField(profile.interests))
		? parseJsonField(profile.interests)
		: [];
	const lookingForArr = Array.isArray(parseJsonField(profile.looking_for))
		? parseJsonField(profile.looking_for)
		: [];
	const prefsRaw: any = parseJsonField(profile.preferences) || {};
	const prefs = typeof prefsRaw === "object" && !Array.isArray(prefsRaw) ? prefsRaw : {};
	const birthDate = profile.date_of_birth || profile.birthdate;

	let earned = 0;
	const missing_required: string[] = [];
	const missing_optional: string[] = [];

	if (photoCount >= 3) earned += 25;
	else missing_required.push("photos");

	if (profile.bio && String(profile.bio).trim().length >= 10) earned += 20;
	else missing_required.push("bio");

	if (birthDate) earned += 10;
	else missing_required.push("age");

	if (profile.location_description || profile.location_latitude) earned += 10;
	else missing_required.push("location");

	if (interestsArr.length >= 3) earned += 10;
	else missing_optional.push("interests");

	if (profile.occupation || (prefs && prefs.occupation)) earned += 5;
	else missing_optional.push("occupation");

	if (profile.education || (prefs && prefs.education)) earned += 5;
	else missing_optional.push("education");

	if (profile.height_cm) earned += 3;
	else missing_optional.push("height");

	if (profile.religion) earned += 3;
	else missing_optional.push("religion");

	if (profile.political_views) earned += 3;
	else missing_optional.push("politics");

	if (profile.drinking_status || (prefs && prefs.drinking)) earned += 3;
	else missing_optional.push("drinking");

	if (profile.smoking_status || (prefs && prefs.smoking)) earned += 3;
	else missing_optional.push("smoking");

	return {
		percentage: earned,
		required_complete: missing_required.length === 0,
		missing_required,
		missing_optional,
		sections: {
			basic: !!(profile.display_name && profile.bio && birthDate && profile.gender),
			location: !!(profile.location_description || profile.location_latitude),
			preferences: !!(
				lookingForArr.length > 0 ||
				prefs.age_range_min ||
				prefs.age_range_max ||
				prefs.height_min ||
				prefs.height_max
			),
			interests: interestsArr.length > 0,
			physical: !!(profile.height_cm || profile.body_type),
			lifestyle: !!(
				profile.drinking_status ||
				prefs.drinking ||
				profile.smoking_status ||
				prefs.smoking ||
				profile.cannabis_status ||
				prefs.cannabis ||
				profile.occupation
			),
		},
	};
}

// Known columns that exist in user_profiles table
const PROFILE_COLUMNS = new Set([
	"id",
	"user_id",
	"display_name",
	"date_of_birth",
	"gender",
	"pronouns",
	"sexual_orientation",
	"relationship_style",
	"looking_for",
	"bio",
	"voice_intro_url",
	"birthdate",
	"location_latitude",
	"location_longitude",
	"location_description",
	"sti_status",
	"preferences",
	"love_language",
	"personality_type",
	"political_views",
	"religion",
	"sleep_schedule",
	"social_media",
	"avatar_url",
	"is_federated",
	"journal_circle_group_id",
	"created_at",
	"updated_at",
	"is_verified",
	"is_id_verified",
	"zk_id_issuer",
	"id_verified_at",
	"verified_at",
	"verification_photo_path",
	"smoking_status",
	"drinking_status",
	"cannabis_status",
	"dietary_preferences",
	"zodiac_sign",
	"relationship_goals",
	"has_children",
	"wants_children",
	"has_pets",
	"languages",
	"interests",
	"height_cm",
	"body_type",
	"hair_color",
	"eye_color",
	"skin_tone",
	"facial_hair",
	"dominant_hand",
	"fitness_level",
	"clothing_style",
	"ethnicity",
	"occupation",
	"education",
	"relationship_status",
	"interested_in",
	"penis_length_cm",
	"penis_girth_cm",
	"fetishes",
	"breast_size",
	"tattoos",
	"piercings",
	"avatar_prompt",
	"avatar_status",
	"preferred_language",
	"is_travel_mode",
	"is_incognito",
	"subscription_price",
	"travel_latitude",
	"travel_longitude",
	"travel_location_name",
	"latitude",
	"longitude",
	"location_name",
	"current_emotion",
	"emotion_updated_at",
]);

// JSON columns that should be stringified before saving
const JSON_COLUMNS = new Set([
	"preferences",
	"looking_for",
	"interests",
	"languages",
	"social_media",
	"sti_status",
	"fetishes",
	"interested_in",
]);

// PUT /api/profile - Update profile
router.put("/", authenticate, async (req: any, res) => {
	try {
		const userId = BigInt(req.user.id);
		const raw = req.body;

		// Map nested objects to flat DB columns
		const data: any = {};
		for (const [key, val] of Object.entries(raw)) {
			if (val === undefined || val === null) continue;
			if (key === "location") {
				const loc = val as any;
				if (loc.latitude != null) data.location_latitude = Number(loc.latitude);
				if (loc.longitude != null)
					data.location_longitude = Number(loc.longitude);
				if (loc.city || loc.state)
					data.location_description = [loc.city, loc.state]
						.filter(Boolean)
						.join(", ");
				if (loc.max_distance != null)
					data.location_name = String(loc.max_distance);
				// Store match_scope, search_country, search_city in preferences
				if (loc.match_scope || loc.search_country || loc.search_city) {
					const existingPrefs =
						typeof raw.preferences === "object" ? raw.preferences : {};
					const mergedPrefs = { ...existingPrefs };
					if (loc.match_scope) mergedPrefs.match_scope = loc.match_scope;
					if (loc.search_country)
						mergedPrefs.search_country = loc.search_country;
					if (loc.search_city) mergedPrefs.search_city = loc.search_city;
				}
				continue; // Don't pass nested 'location' to Prisma
			}
			if (key === "travel_location") {
				const loc = val as any;
				if (loc.latitude != null) data.travel_latitude = Number(loc.latitude);
				if (loc.longitude != null)
					data.travel_longitude = Number(loc.longitude);
				if (loc.name != null) data.travel_location_name = loc.name;
				continue;
			}
			if (key === "voice_intro") continue; // Handled via multipart upload
			if (!PROFILE_COLUMNS.has(key)) continue; // Skip unknown fields
			if (JSON_COLUMNS.has(key)) {
				data[key] = typeof val === "string" ? val : JSON.stringify(val);
			} else {
				data[key] = val;
			}
		}

		// Convert date-only strings to full ISO DateTime for Prisma
		// Sync both date columns so reads always work
		if (data.birthdate && !data.date_of_birth)
			data.date_of_birth = data.birthdate;
		if (data.date_of_birth && !data.birthdate)
			data.birthdate = data.date_of_birth;

		if (
			data.birthdate &&
			typeof data.birthdate === "string" &&
			data.birthdate.length === 10
		) {
			data.birthdate = new Date(data.birthdate + "T00:00:00.000Z");
		}
		if (
			data.date_of_birth &&
			typeof data.date_of_birth === "string" &&
			data.date_of_birth.length === 10
		) {
			data.date_of_birth = new Date(data.date_of_birth + "T00:00:00.000Z");
		}

		// Sync lifestyle preference fields to top-level DB columns
		try {
			const prefs =
				typeof raw.preferences === "string"
					? JSON.parse(raw.preferences)
					: raw.preferences || {};
			if (prefs.drinking && !data.drinking_status)
				data.drinking_status = prefs.drinking;
			if (prefs.smoking && !data.smoking_status)
				data.smoking_status = prefs.smoking;
			if (prefs.cannabis && !data.cannabis_status)
				data.cannabis_status = prefs.cannabis;
			if (prefs.occupation && !data.occupation)
				data.occupation = prefs.occupation;
			if (prefs.education && !data.education) data.education = prefs.education;
		} catch (e) {}

		// Remove fields that should not be set directly
		delete data.id;
		delete data.user_id;
		delete data.created_at;
		delete data.updated_at;

		// Coerce boolean values to strings for string-typed columns
		// (user_profiles has tattoos/piercings as String?, but frontend sends Boolean)
		const STRING_BOOL_COLUMNS = ["tattoos", "piercings"];
		for (const col of STRING_BOOL_COLUMNS) {
			if (col in data && typeof data[col] === "boolean") {
				data[col] = data[col] ? "true" : "false";
			}
		}
		// Coerce boolean values for actual Boolean columns (ensure proper type)
		const BOOL_COLUMNS = ["has_children", "wants_children", "has_pets"];
		for (const col of BOOL_COLUMNS) {
			if (col in data && typeof data[col] === "string") {
				data[col] = data[col] === "true" || data[col] === "1";
			}
		}

		const existing = await prisma.user_profiles.findFirst({
			where: { user_id: userId },
		});
		// Merge preferences instead of overwriting
		if (existing && data.preferences) {
			try {
				const existingPrefs =
					typeof existing.preferences === "string"
						? JSON.parse(existing.preferences)
						: existing.preferences || {};
				const incomingPrefs =
					typeof data.preferences === "string"
						? JSON.parse(data.preferences)
						: data.preferences;
				data.preferences = JSON.stringify({
					...existingPrefs,
					...incomingPrefs,
				});
			} catch (e) {}
		}
		if (existing) {
			const updated = await prisma.user_profiles.update({
				where: { id: existing.id },
				data,
			});
			res.json(serialize(updated));
		} else {
			const created = await prisma.user_profiles.create({
				data: { user_id: userId, ...data },
			});
			res.json(serialize(created));
		}
	} catch (error: any) {
		console.error("[PUT /api/profile]", error.message);
		res
			.status(500)
			.json({ message: error.message || "Failed to update profile" });
	}
});

// DELETE /api/profile - Delete current user's account (GDPR)
router.delete("/", authenticate, async (req: any, res) => {
	try {
		const userId = BigInt(req.user.id);

		// Delete related records in order (respecting foreign keys)
		await prisma.$transaction([
			prisma.photos.deleteMany({ where: { user_id: userId } }),
			prisma.user_profiles.deleteMany({ where: { user_id: userId } }),
			prisma.matches.deleteMany({ where: { user1_id: userId } }),
			prisma.matches.deleteMany({ where: { user2_id: userId } }),
			prisma.messages.deleteMany({ where: { sender_id: userId } }),
			prisma.blocks.deleteMany({ where: { blocker_id: userId } }),
			prisma.blocks.deleteMany({ where: { blocked_id: userId } }),
			prisma.reports.deleteMany({ where: { reporter_id: userId } }),
			prisma.api_tokens.deleteMany({ where: { user_id: userId } }),
		]);

		// Delete the user account itself
		await prisma.users.delete({ where: { id: userId } });

		res.json({ message: "Account deleted successfully" });
	} catch (error: any) {
		console.error("[DELETE /api/profile]", error);
		res
			.status(500)
			.json({ message: error.message || "Failed to delete account" });
	}
});

// GET /api/profile/completeness
router.get("/completeness", authenticate, async (req: any, res) => {
	try {
		const userId = BigInt(req.user.id);
		const profile = await prisma.user_profiles.findFirst({
			where: { user_id: userId },
		});

		if (!profile) {
			return res.json(getEmptyCompletenessSummary());
		}

		res.json(await calculateProfileCompletenessSummary(userId, profile));
	} catch (err) {
		res.json(getEmptyCompletenessSummary());
	}
});

// GET /api/profile/:id/views - Profile view history
router.get("/:id/views", authenticate, async (req: any, res) => {
	res.json({ views: [], total: 0 });
});

// GET /api/profile/:id/view-stats - Profile view statistics
router.get("/:id/view-stats", authenticate, async (req: any, res) => {
	res.json({ total_views: 0, unique_viewers: 0, today: 0, this_week: 0 });
});

// GET /api/users/search - Search users
router.get("/search", authenticate, async (req: any, res) => {
	try {
		const { q } = req.query;
		if (!q) return res.json([]);

		const users = await prisma.users.findMany({
			where: {
				OR: [
					{ name: { contains: String(q) } },
					{ email: { contains: String(q) } },
				],
			},
			take: 20,
			select: {
				id: true,
				name: true,
				email: true,
				user_profiles: {
					select: {
						bio: true,
						location_description: true,
						display_name: true,
					},
				},
			},
		});

		res.json(
			(users as any).map((u: any) => {
				const p = Array.isArray(u.user_profiles) ? u.user_profiles[0] : (u as any).user_profiles;
				return {
					id: Number(u.id),
					email: u.email,
					profile: {
						display_name: p?.display_name || u.name,
						bio: (p as any)?.bio,
						location: {
							city: (p as any)?.location_description?.split(",")[0] || "",
						},
					},
				};
			}),
		);
	} catch (err) {
		res.status(500).json({ message: "Search failed" });
	}
});

// GET /api/users/:id - Get public profile
router.get("/:id", authenticate, async (req: any, res) => {
	try {
		const targetUserId = BigInt(req.params.id);
		const user = await prisma.users.findUnique({
			where: { id: targetUserId },
			include: { user_profiles: true },
		});

		if (!user) return res.status(404).json({ message: "User not found" });

		const profile = Array.isArray(user.user_profiles) ? user.user_profiles[0] : user.user_profiles;
		const photos = await prisma.photos.findMany({
			where: { user_id: targetUserId, is_private: false },
			orderBy: { is_primary: "desc" },
		});

		// Basic response similar to /api/profile but for public use
		const p = profile ? serialize(profile) : {};
		if (profile) {
			for (const col of [
				"preferences",
				"looking_for",
				"interests",
				"languages",
				"social_media",
				"sti_status",
				"fetishes",
			]) {
				p[col] = parseJsonField(p[col]);
			}
		}

		// Fetch relationship links
		const relationshipLinks = await prisma.relationship_links.findMany({
			where: { user_id: targetUserId, is_confirmed: true, visibility: 'public' },
			include: {
				users_relationship_links_related_user_idTousers: {
					select: {
						id: true,
						name: true,
						user_profiles: {
							select: {
								display_name: true,
								avatar_url: true,
							},
						},
					},
				},
			},
		});

		res.json({
			id: Number(targetUserId),
			email_verified: !!user.email_verified_at,
			created_at: user.created_at?.toISOString() || "",
			last_online: user.last_seen_at?.toISOString() || "",
			profile: {
				display_name: p.display_name || user.name || "User",
				bio: p.bio || "No bio yet.",
				age:
					p.date_of_birth || p.birthdate
						? Math.floor(
								(Date.now() -
									new Date(p.date_of_birth || p.birthdate).getTime()) /
									(365.25 * 24 * 60 * 60 * 1000),
							)
						: null,
				gender: p.gender,
				location: {
					city: p.location_description?.split(",")[0] || "",
					state: p.location_description?.split(",")[1]?.trim() || "",
				},
				looking_for: p.looking_for || [],
				interests: p.interests || [],
				photos: photos.map((ph: any) => ({
					id: Number(ph.id),
					url: ph.file_path || "",
					is_private: ph.is_private || false,
					is_primary: ph.is_primary || false,
				})),
				vouches: [],
				relationship_links: relationshipLinks.map((l: any) => ({
					id: Number(l.id),
					relationship_type: l.relationship_type,
					relationship_type_label:
						l.relationship_type.charAt(0).toUpperCase() +
						l.relationship_type.slice(1),
					visibility: l.visibility,
					visibility_label:
						l.visibility.charAt(0).toUpperCase() + l.visibility.slice(1),
					note: l.note,
					confirmed_at: l.confirmed_at?.toISOString(),
					is_confirmed: l.is_confirmed,
					requested_by_user_id: Number(l.requested_by_user_id),
					related_user: l.users_relationship_links_related_user_idTousers
						? {
								id: Number(l.users_relationship_links_related_user_idTousers.id),
								name: l.users_relationship_links_related_user_idTousers.name,
								display_name:
									l.users_relationship_links_related_user_idTousers
										.user_profiles?.[0]?.display_name,
								avatar_url:
									l.users_relationship_links_related_user_idTousers
										.user_profiles?.[0]?.avatar_url,
							}
						: null,
				})),
				scene_summary: null,
				journals: [],
			},
		});
	} catch (error: any) {
		console.error("[GET /api/users/:id]", error.message);
		res.status(500).json({ message: "Failed to load profile" });
	}
});

export default router;
