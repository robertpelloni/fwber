import { StyleSheet, View, BackHandler, Linking } from 'react-native';
import { WebView } from 'react-native-webview';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useEffect, useRef, useState } from 'react';
import * as Location from 'expo-location';

const TARGET_DOMAIN = 'fwber.me';

export default function Home() {
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
