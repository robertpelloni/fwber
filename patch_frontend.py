import re

with open('fwber-frontend/components/quests/QuestBoard.tsx', 'r') as f:
    content = f.read()

# Add imports
content = content.replace("import { api } from '@/lib/api/client';", "import { api } from '@/lib/api/client';\nimport { generateSHA256 } from '@/lib/utils/hash';")
content = content.replace("import { Trophy, Zap, MapPin, CheckCircle2, Coins, ArrowRight } from 'lucide-react';", "import { Trophy, Zap, MapPin, CheckCircle2, Coins, ArrowRight, ScanLine } from 'lucide-react';")

# Replace handleComplete signature and body
new_handleComplete = """
    const handleComplete = async (quest: Quest) => {
        try {
            let proof = undefined;

            // Generate proof if the quest requires it (simulating an NFC scan or ZK proof)
            if (quest.verification_secret) {
                // In a real scenario, this secret would be read from an NFC tag, not exposed via API
                // For demo purposes, we read it from the quest object
                const userId = token ? JSON.parse(atob(token.split('.')[1])).id : 0;
                proof = await generateSHA256(`${quest.id}${userId}${quest.verification_secret}`);
            }

            const res = await api.post<{ success: boolean; reward: number }>(`/quests/${quest.id}/complete`, { proof }, {
                headers: { Authorization: `Bearer ${token}` }
            });
            alert(`Quest Complete! You earned ${res.reward} FWB tokens.`);
            fetchQuests();
        } catch (err: any) {
            alert(err.response?.data?.error || 'Condition not met yet. Keep going!');
        }
    };
"""

# Regex substitute handleComplete
content = re.sub(r'const handleComplete = async \(id: number\) => \{.*?\n    \};', new_handleComplete.strip(), content, flags=re.DOTALL)

# Update onClick handler
content = content.replace("handleComplete(quest.id)", "handleComplete(quest)")

# Update button text conditionally
btn_replacement = """
                                            <Button onClick={() => handleComplete(quest)} className="w-full bg-amber-500 text-white hover:bg-amber-600 h-9 text-xs font-bold uppercase tracking-widest rounded-xl">
                                                {quest.verification_secret ? <><ScanLine className="w-4 h-4 mr-2" /> Scan NFC to Verify</> : "Complete Task"}
                                            </Button>
"""

content = re.sub(r'<Button onClick=\{\(\) => handleComplete\(quest\)\} className="w-full bg-amber-500.*?Complete Task\n.*?<\/Button>', btn_replacement.strip(), content, flags=re.DOTALL)


with open('fwber-frontend/components/quests/QuestBoard.tsx', 'w') as f:
    f.write(content)
