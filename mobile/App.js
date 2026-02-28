import { StatusBar } from 'expo-status-bar';
import { StyleSheet, View, BackHandler, Linking } from 'react-native';
import { WebView } from 'react-native-webview';
import { SafeAreaProvider, useSafeAreaInsets } from 'react-native-safe-area-context';
import { useEffect, useRef, useState } from 'react';
import * as Location from 'expo-location';

const TARGET_DOMAIN = 'fwber.me';

function WebViewContainer() {
  const insets = useSafeAreaInsets();
  const webViewRef = useRef(null);
  const [canGoBack, setCanGoBack] = useState(false);
  const [locationGranted, setLocationGranted] = useState(false);

  // 1. Request Native Location Permissions on Mount
  useEffect(() => {
    (async () => {
      const { status } = await Location.requestForegroundPermissionsAsync();
      setLocationGranted(status === 'granted');
    })();
  }, []);

  // 2. Handle Android Hardware Back Button
  useEffect(() => {
    const handleBackPress = () => {
      if (canGoBack && webViewRef.current) {
        webViewRef.current.goBack();
        return true; // Prevent default OS exit
      }
      return false; // Allow exit if we are at the app root
    };

    BackHandler.addEventListener('hardwareBackPress', handleBackPress);
    return () => BackHandler.removeEventListener('hardwareBackPress', handleBackPress);
  }, [canGoBack]);

  // 3. Intercept External Links
  const handleNavigationStateChange = (navState) => {
    setCanGoBack(navState.canGoBack);

    const { url } = navState;
    if (!url) return;

    try {
      const hostname = new URL(url).hostname;
      // If navigating to Stripe, Twitter, etc., pop out to system browser
      if (!hostname.includes(TARGET_DOMAIN)) {
        webViewRef.current.stopLoading(); // Stop webview from redirecting
        Linking.openURL(url); // Push to Safari/Chrome natively
      }
    } catch (error) {
      // Ignore invalid URLs
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
        geolocationEnabled={locationGranted} // Bind Native -> Web API
      />
      <StatusBar style="auto" />
    </View>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <WebViewContainer />
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000', // Matches common fwber dark theme boundary
  },
  webview: {
    flex: 1,
    backgroundColor: 'transparent',
  }
});
