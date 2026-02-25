export default function TermsPage() {
  return (
    <div className="container mx-auto py-12 px-4 max-w-4xl">
      <h1 className="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-500 mb-6">Terms of Service</h1>
      <p className="mb-8 text-muted-foreground">Last updated: {new Date().toLocaleDateString()}</p>

      <div className="prose dark:prose-invert prose-purple max-w-none">
        <h2 className="text-xl font-semibold mt-6 mb-2">1. Acceptance of Terms & Age Restriction</h2>
        <p>By accessing and using fwber, you accept and agree to be bound by the terms and provision of this agreement. <strong>fwber is an adult social network. You must be at least 18 years of age (or the legal age of majority in your jurisdiction) to create an account or use this service.</strong></p>

        <h2 className="text-xl font-semibold mt-6 mb-2">2. User Conduct, Content, and Zero Tolerance</h2>
        <p>You are solely responsible for your interactions and the content you upload. We enforce a zero-tolerance policy against:</p>
        <ul>
          <li>Non-consensual sharing of intimate images (NCII).</li>
          <li>Minors or the sexualization of minors.</li>
          <li>Harassment, stalking, or targeted abuse of other members.</li>
          <li>Solicitation of illegal services or non-consensual commercial spam.</li>
        </ul>
        <p>Violations will result in immediate permanent banning and potential reports to law enforcement.</p>

        <h2 className="text-xl font-semibold mt-6 mb-2">3. Virtual Tokens (FWB) and Transactions</h2>
        <p>fwber utilizes a virtual token economy (FWB Tokens) for unlocking premium features, entering chatrooms, and sending gifts. FWB tokens have no real-world cash value outside the platform unless explicitly redeemed through our authorized Merchant Analytics program. All token purchases are final and non-refundable.</p>

        <h2 className="text-xl font-semibold mt-6 mb-2">4. Privacy and Cryptographic Features</h2>
        <p>While we provide advanced privacy tools like Ghost Mode, Location Fuzzing, and End-to-End Encrypted chatrooms, these tools are provided "as is". You understand that interacting with users in proximity feeds carries inherent physical risks. Always meet in public venues for first encounters.</p>

        <h2 className="text-xl font-semibold mt-6 mb-2">5. Disclaimer of Warranties</h2>
        <p>The materials on fwber&apos;s website are provided on an &apos;as is&apos; basis. fwber makes no warranties, expressed or implied, regarding the conduct of its members or the accuracy of their profiles.</p>
      </div>
    </div>
  );
}
