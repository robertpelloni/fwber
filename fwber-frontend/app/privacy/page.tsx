export default function PrivacyPage() {
  return (
    <div className="container mx-auto py-12 px-4 max-w-4xl">
      <h1 className="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-500 mb-6">Privacy Policy</h1>
      <p className="mb-8 text-muted-foreground">Last updated: {new Date().toLocaleDateString()}</p>

      <div className="prose dark:prose-invert prose-purple max-w-none">
        <h2 className="text-xl font-semibold mt-6 mb-2">1. The Privacy Paradigm</h2>
        <p>At fwber, privacy is not an afterthought; it is our foundation. As a decentralized, proximity-based adult social network, we recognize the extreme sensitivity of the data you entrust to us. <strong>We do not sell your personal data or chat histories to third-party data brokers or advertisers.</strong></p>

        <h2 className="text-xl font-semibold mt-6 mb-2">2. Information We Collect</h2>
        <ul>
          <li><strong>Location Data:</strong> To enable proximity matching, we collect real-time Geohashes. When you use "Location Fuzzing", your precise GPS coordinates are obscured before touching our servers.</li>
          <li><strong>Biometric Data (Face Blur):</strong> Our real-time video face blurring occurs <em>on your device</em> (Client-Side). We do not transmit or store your raw biometric face meshes.</li>
          <li><strong>Cryptographic Identities:</strong> If you use the Solana integration, your public wallet address acts as part of your identity.</li>
        </ul>

        <h2 className="text-xl font-semibold mt-6 mb-2">3. End-to-End Encryption (E2EE) & Communications</h2>
        <p>Private messages sent within fwber utilize high-grade encryption. While metadata (who messaged whom) is stored to operate the service, the contents of your messages are designed to be incomprehensible to anyone other than the intended recipient.</p>

        <h2 className="text-xl font-semibold mt-6 mb-2">4. Ghost Mode & Visibility</h2>
        <p>You control your visibility. "Ghost Mode" completely removes you from the Local Pulse and public search algorithms, allowing you to browse without leaving a trace.</p>

        <h2 className="text-xl font-semibold mt-6 mb-2">5. GDPR & CCPA Rights</h2>
        <p>You have the absolute right to view or delete your data. Use the "Export Data" tool in Settings to download a JSON archive of your profile, or the "Delete Account" button to permanently scrub your presence from our servers.</p>
      </div>
    </div>
  );
}
