'use client';

import React from 'react';
import { useNotificationPreferences, useUpdateNotificationPreference } from '@/lib/hooks/use-notifications';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Bell, Mail, Database } from 'lucide-react';
import { useToast } from '@/components/ToastProvider';

export default function NotificationSettingsPage() {
  const { data: preferences, isLoading } = useNotificationPreferences();
  const updatePreference = useUpdateNotificationPreference();
  const toast = useToast();

  const handleToggle = (type: string, channel: 'mail' | 'push' | 'database', value: boolean) => {
    updatePreference.mutate(
      { type, data: { [channel]: value } },
      {
        onError: () => {
          toast.showError('Error', 'Failed to update preference');
        },
      }
    );
  };

  if (isLoading) {
    return <div className="p-8 text-center">Loading settings...</div>;
  }

  return (
    <div className="container mx-auto py-8 px-4 max-w-3xl">
      <h1 className="text-3xl font-bold mb-6">Notification Settings</h1>
      
      <div className="space-y-6">
        {preferences?.map((pref) => (
          <Card key={pref.type}>
            <CardHeader className="pb-3">
              <CardTitle className="text-lg font-medium">{pref.label}</CardTitle>
              <CardDescription>Manage how you receive notifications for {pref.label.toLowerCase()}.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Mail className="w-5 h-5 text-muted-foreground" />
                    <div>
                      <p className="font-medium">Email Notifications</p>
                      <p className="text-sm text-muted-foreground">Receive updates via email</p>
                    </div>
                  </div>
                  <Switch
                    checked={pref.mail}
                    onCheckedChange={(checked) => handleToggle(pref.type, 'mail', checked)}
                    disabled={updatePreference.isPending}
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Bell className="w-5 h-5 text-muted-foreground" />
                    <div>
                      <p className="font-medium">Push Notifications</p>
                      <p className="text-sm text-muted-foreground">Receive push notifications on your device</p>
                    </div>
                  </div>
                  <Switch
                    checked={pref.push}
                    onCheckedChange={(checked) => handleToggle(pref.type, 'push', checked)}
                    disabled={updatePreference.isPending}
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Database className="w-5 h-5 text-muted-foreground" />
                    <div>
                      <p className="font-medium">In-App Notifications</p>
                      <p className="text-sm text-muted-foreground">Show in the notification center</p>
                    </div>
                  </div>
                  <Switch
                    checked={pref.database}
                    onCheckedChange={(checked) => handleToggle(pref.type, 'database', checked)}
                    disabled={updatePreference.isPending}
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
