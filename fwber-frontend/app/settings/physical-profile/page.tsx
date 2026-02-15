'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { apiClient } from '@/lib/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, ArrowLeft, User, Ruler, Sparkles } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { useRouter } from 'next/navigation';

interface PhysicalProfile {
  height_cm?: number;
  body_type?: string;
  hair_color?: string;
  eye_color?: string;
  skin_tone?: string;
  ethnicity?: string;
  facial_hair?: string;
  tattoos?: boolean;
  piercings?: boolean;
  dominant_hand?: 'left' | 'right' | 'ambi';
  fitness_level?: 'low' | 'average' | 'fit' | 'athletic';
  clothing_style?: string;
  avatar_prompt?: string;
  avatar_status?: string;
}

export default function PhysicalProfilePage() {
  const { token } = useAuth();
  const router = useRouter();
  const { toast } = useToast();
  const [profile, setProfile] = useState<PhysicalProfile>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [generating, setGenerating] = useState(false);

  useEffect(() => {
    if (token) {
      fetchProfile();
    }
  }, [token]);

  const fetchProfile = async () => {
    try {
      const response = await apiClient.get<{ data: PhysicalProfile }>('/physical-profile');
      if (response.data.data) {
        setProfile(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch physical profile', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    setSaving(true);
    try {
      await apiClient.put('/physical-profile', profile);
      toast({
        title: "Profile Saved",
        description: "Your physical profile has been updated.",
      });
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to save profile. Please check your inputs.",
        variant: "destructive"
      });
    } finally {
      setSaving(false);
    }
  };

  const handleGenerateAvatar = async () => {
    if (!profile.avatar_prompt) {
        toast({
            title: "Prompt Required",
            description: "Please enter an avatar prompt first.",
            variant: "destructive"
        });
        return;
    }

    // Save first to ensure prompt is stored
    await handleSave();

    setGenerating(true);
    try {
      await apiClient.post('/physical-profile/avatar/request', { style: 'realistic' });
      toast({
        title: "Generation Started",
        description: "Your AI avatar is being generated. This may take a few minutes.",
      });
      setProfile(prev => ({ ...prev, avatar_status: 'requested' }));
    } catch (error) {
        toast({
            title: "Error",
            description: "Failed to request avatar generation.",
            variant: "destructive"
        });
    } finally {
      setGenerating(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader2 className="w-8 h-8 animate-spin text-purple-500" />
      </div>
    );
  }

  return (
    <div className="container mx-auto py-8 px-4 max-w-2xl">
        <Button variant="ghost" className="mb-4 pl-0 hover:pl-0" onClick={() => router.back()}>
            <ArrowLeft className="w-4 h-4 mr-2" /> Back to Settings
        </Button>

        <h1 className="text-3xl font-bold mb-2">Physical Profile</h1>
        <p className="text-muted-foreground mb-8">
            Define your physical attributes to generate your AI Avatar.
            This data is private and used only for rendering your digital twin.
        </p>

        <form onSubmit={handleSave} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        <Ruler className="w-5 h-5 text-purple-500" /> Measurements & Build
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label>Height (cm)</Label>
                        <Input
                            type="number"
                            min={80}
                            max={250}
                            value={profile.height_cm || ''}
                            onChange={e => setProfile({...profile, height_cm: parseInt(e.target.value) || undefined})}
                            placeholder="175"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Body Type</Label>
                        <Input
                            value={profile.body_type || ''}
                            onChange={e => setProfile({...profile, body_type: e.target.value})}
                            placeholder="e.g. Athletic, Slim, Curvy"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Fitness Level</Label>
                        <select
                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            value={profile.fitness_level || ''}
                            onChange={(e) => setProfile({...profile, fitness_level: e.target.value as any})}
                        >
                            <option value="" disabled>Select level</option>
                            <option value="low">Low</option>
                            <option value="average">Average</option>
                            <option value="fit">Fit</option>
                            <option value="athletic">Athletic</option>
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Dominant Hand</Label>
                        <select
                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            value={profile.dominant_hand || ''}
                            onChange={(e) => setProfile({...profile, dominant_hand: e.target.value as any})}
                        >
                            <option value="" disabled>Select hand</option>
                            <option value="right">Right</option>
                            <option value="left">Left</option>
                            <option value="ambi">Ambidextrous</option>
                        </select>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        <User className="w-5 h-5 text-purple-500" /> Appearance
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label>Hair Color</Label>
                        <Input
                            value={profile.hair_color || ''}
                            onChange={e => setProfile({...profile, hair_color: e.target.value})}
                            placeholder="e.g. Dark Brown"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Eye Color</Label>
                        <Input
                            value={profile.eye_color || ''}
                            onChange={e => setProfile({...profile, eye_color: e.target.value})}
                            placeholder="e.g. Hazel"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Skin Tone</Label>
                        <Input
                            value={profile.skin_tone || ''}
                            onChange={e => setProfile({...profile, skin_tone: e.target.value})}
                            placeholder="e.g. Olive, Fair"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Ethnicity</Label>
                        <Input
                            value={profile.ethnicity || ''}
                            onChange={e => setProfile({...profile, ethnicity: e.target.value})}
                            placeholder="Optional"
                        />
                    </div>
                    <div className="space-y-2 sm:col-span-2">
                        <Label>Facial Hair</Label>
                        <Input
                            value={profile.facial_hair || ''}
                            onChange={e => setProfile({...profile, facial_hair: e.target.value})}
                            placeholder="e.g. Clean shaven, Beard"
                        />
                    </div>
                    <div className="flex items-center space-x-2">
                        <Switch
                            checked={profile.tattoos || false}
                            onCheckedChange={checked => setProfile({...profile, tattoos: checked})}
                        />
                        <Label>Has Tattoos</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Switch
                            checked={profile.piercings || false}
                            onCheckedChange={checked => setProfile({...profile, piercings: checked})}
                        />
                        <Label>Has Piercings</Label>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        <Sparkles className="w-5 h-5 text-purple-500" /> Style & Avatar
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>Clothing Style</Label>
                        <Input
                            value={profile.clothing_style || ''}
                            onChange={e => setProfile({...profile, clothing_style: e.target.value})}
                            placeholder="e.g. Casual, Streetwear, Formal"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Avatar Prompt (Describe yourself for the AI)</Label>
                        <textarea
                            className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            value={profile.avatar_prompt || ''}
                            onChange={e => setProfile({...profile, avatar_prompt: e.target.value})}
                            placeholder="A photorealistic portrait of a young man with curly hair..."
                        />
                        <p className="text-xs text-muted-foreground">
                            Combine your physical traits into a descriptive prompt.
                        </p>
                    </div>

                    <div className="pt-4 border-t flex justify-between items-center">
                        <div className="text-sm">
                            Status: <span className="font-semibold capitalize">{profile.avatar_status || 'Not Started'}</span>
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleGenerateAvatar}
                            disabled={generating || !profile.avatar_prompt}
                        >
                            {generating ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Sparkles className="w-4 h-4 mr-2" />}
                            Generate Avatar
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-4">
                <Button type="button" variant="outline" onClick={() => router.back()}>Cancel</Button>
                <Button type="submit" disabled={saving}>
                    {saving ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                    Save Changes
                </Button>
            </div>
        </form>
    </div>
  );
}
