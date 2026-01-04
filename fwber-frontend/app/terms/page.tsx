export default function TermsPage() {
  return (
    <div className="container mx-auto py-12 px-4 max-w-4xl">
      <h1 className="text-3xl font-bold mb-6">Terms of Service</h1>
      <p className="mb-4 text-muted-foreground">Last updated: {new Date().toLocaleDateString()}</p>
      
      <div className="prose dark:prose-invert max-w-none">
        <p>Welcome to fwber. By using our services, you agree to these terms.</p>
        
        <h2 className="text-xl font-semibold mt-6 mb-2">1. Acceptance of Terms</h2>
        <p>By accessing and using fwber, you accept and agree to be bound by the terms and provision of this agreement.</p>
        
        <h2 className="text-xl font-semibold mt-6 mb-2">2. Use License</h2>
        <p>Permission is granted to temporarily download one copy of the materials (information or software) on fwber's website for personal, non-commercial transitory viewing only.</p>
        
        <h2 className="text-xl font-semibold mt-6 mb-2">3. Disclaimer</h2>
        <p>The materials on fwber's website are provided on an 'as is' basis. fwber makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
      </div>
    </div>
  );
}
