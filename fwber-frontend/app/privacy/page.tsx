export default function PrivacyPage() {
  return (
    <div className="container mx-auto py-12 px-4 max-w-4xl">
      <h1 className="text-3xl font-bold mb-6">Privacy Policy</h1>
      <p className="mb-4 text-muted-foreground">Last updated: {new Date().toLocaleDateString()}</p>
      
      <div className="prose dark:prose-invert max-w-none">
        <p>Your privacy is important to us. It is fwber&apos;s policy to respect your privacy regarding any information we may collect from you across our website.</p>
        
        <h2 className="text-xl font-semibold mt-6 mb-2">1. Information We Collect</h2>
        <p>We only ask for personal information when we truly need it to provide a service to you. We collect it by fair and lawful means, with your knowledge and consent.</p>
        
        <h2 className="text-xl font-semibold mt-6 mb-2">2. How We Use Information</h2>
        <p>We use the information we collect to operate and maintain our services, improve user experience, and communicate with you.</p>
        
        <h2 className="text-xl font-semibold mt-6 mb-2">3. Data Security</h2>
        <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it.</p>
      </div>
    </div>
  );
}
