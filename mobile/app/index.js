import { StyleSheet, View, BackHandler, Linking, Platform } from 'react-native';
import { WebView } from 'react-native-webview';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useEffect, useRef, useState } from 'react';
import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import * as SecureStore from 'expo-secure-store';
import * as Notifications from 'expo-notifications';
import NfcManager, { NfcTech, Ndef } from 'react-native-nfc-manager';

const TARGET_DOMAIN = 'fwber.me';
const LOCATION_TASK_NAME = 'background-location-task';

// Define the background task for location tracking
TaskManager.defineTask(LOCATION_TASK_NAME, async ({ data, error }) => {
  if (error) {
    console.error('Background location task error:', error);
    return;
  }
  if (data) {
    const { locations } = data;
    if (locations && locations.length > 0) {
      const location = locations[0];
      try {
        // Retrieve token from SecureStore for background API call
        const userToken = await SecureStore.getItemAsync('userToken');
        if (!userToken) return;

        console.log('Background location received:', location.coords.latitude, location.coords.longitude);
        
        await fetch(`https://api.${TARGET_DOMAIN}/api/location`, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${userToken}`
          },
          body: JSON.stringify({
            latitude: location.coords.latitude,
            longitude: location.coords.longitude,
            accuracy: location.coords.accuracy,
          })
        });
      } catch (err) {
        console.error('Failed to update background location', err);
      }
    }
  }
});

export default function Home() {
  const insets = useSafeAreaInsets();
  const webViewRef = useRef(null);
  const [canGoBack, setCanGoBack] = useState(false);
  const [locationGranted, setLocationGranted] = useState(false);
  const [nfcSupported, setNfcSupported] = useState(false);

  // 1. Initialize Native Capabilities
  useEffect(() => {
    (async () => {
      // Location Permissions
      const { status: foregroundStatus } = await Location.requestForegroundPermissionsAsync();
      if (foregroundStatus === 'granted') {
        const { status: backgroundStatus } = await Location.requestBackgroundPermissionsAsync();
        if (backgroundStatus === 'granted') {
           setLocationGranted(true);
           // Start tracking
           await Location.startLocationUpdatesAsync(LOCATION_TASK_NAME, {
             accuracy: Location.Accuracy.Balanced,
             timeInterval: 60000, // 1 minute
             distanceInterval: 50, // 50 meters
             foregroundService: {
               notificationTitle: "fwber is active",
               notificationBody: "Monitoring nearby matches...",
               notificationColor: "#FF1493"
             }
           });
        }
      }

      // NFC
      try {
        const supported = await NfcManager.isSupported();
        setNfcSupported(supported);
        if (supported) {
          await NfcManager.start();
        }
      } catch (e) {
        console.warn('NFC initialization failed', e);
      }

      // Notifications
      const { status: existingStatus } = await Notifications.getPermissionsAsync();
      let finalStatus = existingStatus;
      if (existingStatus !== 'granted') {
        const { status } = await Notifications.requestPermissionsAsync();
        finalStatus = status;
      }
      if (finalStatus === 'granted') {
        const token = (await Notifications.getExpoPushTokenAsync()).data;
        console.log('Push Token:', token);
        await SecureStore.setItemAsync('pushToken', token);
      }
    })();
  }, []);

  // 2. NFC Native -> WebView Bridge
  useEffect(() => {
    if (!nfcSupported) return;

    const startNfcDiscovery = async () => {
        try {
            await NfcManager.requestTechnology(NfcTech.Ndef);
            const tag = await NfcManager.getTag();
            
            if (tag && tag.ndefMessage) {
                const message = Ndef.decodeMessage(tag.ndefMessage[0].payload);
                const payload = Ndef.text.decodePayload(tag.ndefMessage[0].payload);
                
                // Inject into WebView
                const js = `
                  if (window.handleNativeNFC) {
                    window.handleNativeNFC(${JSON.stringify(payload)});
                  }
                `;
                webViewRef.current?.injectJavaScript(js);
            }
        } catch (ex) {
            console.warn('NFC Read Error', ex);
        } finally {
            NfcManager.cancelTechnologyRequest();
        }
    };

    // We can trigger this via a message from the WebView
  }, [nfcSupported]);

  // 3. WebView -> Native Bridge
  const onMessage = async (event) => {
    try {
      const data = JSON.parse(event.nativeEvent.data);
      
      if (data.type === 'SET_AUTH_TOKEN') {
        // Store token for background tasks
        await SecureStore.setItemAsync('userToken', data.token);

        // Sync push token if we have one
        const pushToken = await SecureStore.getItemAsync('pushToken');
        if (pushToken) {
           await fetch(`https://api.${TARGET_DOMAIN}/api/device-tokens`, {
             method: 'POST',
             headers: { 
               'Content-Type': 'application/json',
               'Authorization': `Bearer ${data.token}`
             },
             body: JSON.stringify({
               token: pushToken,
               platform: Platform.OS
             })
           });
        }
      }

      if (data.type === 'START_NFC_SCAN') {
        // Trigger native NFC prompt
        NfcManager.registerTagEvent();
      }

      if (data.type === 'NFC_WRITE') {
        await NfcManager.requestTechnology(NfcTech.Ndef);
        const bytes = Ndef.encodeMessage([Ndef.textRecord(data.payload)]);
        await NfcManager.ndefHandler.writeNdefMessage(bytes);
        NfcManager.cancelTechnologyRequest();
      }
    } catch (e) {
      console.error('Bridge Error', e);
    }
  };

  // 4. Handle Android Hardware Back Button
  useEffect(() => {
    const handleBackPress = () => {
      if (canGoBack && webViewRef.current) {
        webViewRef.current.goBack();
        return true;
      }
      return false;
    };

    BackHandler.addEventListener('hardwareBackPress', handleBackPress);
    return () => BackHandler.removeEventListener('hardwareBackPress', handleBackPress);
  }, [canGoBack]);

  const handleNavigationStateChange = (navState) => {
    setCanGoBack(navState.canGoBack);
    const { url } = navState;
    if (!url) return;

    try {
      const hostname = new URL(url).hostname;
      if (!hostname.includes(TARGET_DOMAIN) && !url.includes('localhost')) {
        webViewRef.current.stopLoading();
        Linking.openURL(url);
      }
    } catch (error) {
      // Ignore
    }
  };

  return (
    <View style={[styles.container, { paddingTop: insets.top, paddingBottom: insets.bottom }]}>
      <WebView
        ref={webViewRef}
        source={{ uri: `https://${TARGET_DOMAIN}` }}
        style={styles.webview}
        bounces={false}
        showsVerticalScrollIndicator={false}
        onNavigationStateChange={handleNavigationStateChange}
        onMessage={onMessage}
        geolocationEnabled={locationGranted}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  webview: {
    flex: 1,
    backgroundColor: 'transparent',
  }
});
