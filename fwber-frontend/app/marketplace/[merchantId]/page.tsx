'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { marketplaceApi, InventoryItem } from '@/lib/api/marketplace';
import { useAuth } from '@/lib/auth-context';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { ShoppingBag, ArrowLeft, Loader2, Coins, CheckCircle2, QrCode } from 'lucide-react';
import AppHeader from '@/components/AppHeader';
import Image from 'next/image';
import { motion, AnimatePresence } from 'framer-motion';

export default function MarketplacePage() {
    const params = useParams();
    const merchantId = params.merchantId as string;
    const { token, user } = useAuth();
    const { toast } = useToast();
    const router = useRouter();

    const [items, setItems] = useState<InventoryItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [purchasingId, setPurchasingId] = useState<number | null>(null);
    const [redemptionCode, setRedemptionCode] = useState<string | null>(null);

    useEffect(() => {
        if (merchantId) {
            marketplaceApi.getInventory(merchantId)
                .then(res => setItems(res.items))
                .finally(() => setLoading(false));
        }
    }, [merchantId]);

    const handlePurchase = async (item: InventoryItem) => {
        if (!confirm(`Spend ${item.price_tokens} FWB Tokens on "${item.name}"?`)) return;

        try {
            setPurchasingId(item.id);
            const res = await marketplaceApi.purchaseItem(item.id);
            setRedemptionCode(res.redemption_code);
            toast({
                title: "Purchase Successful!",
                description: `You bought ${item.name}.`,
            });
        } catch (err: any) {
            toast({
                variant: "destructive",
                title: "Purchase Failed",
                description: err.message || "Insufficient tokens or out of stock.",
            });
        } finally {
            setPurchasingId(null);
        }
    };

    return (
        <div className="min-h-screen bg-zinc-50 dark:bg-zinc-950">
            <AppHeader />
            <main className="max-w-4xl mx-auto px-4 py-8">
                <div className="flex items-center gap-4 mb-8">
                    <Button variant="ghost" size="icon" onClick={() => router.back()}>
                        <ArrowLeft className="w-5 h-5" />
                    </Button>
                    <div>
                        <h1 className="text-2xl font-black italic uppercase tracking-tighter">Venue Marketplace</h1>
                        <p className="text-sm text-zinc-500 font-bold uppercase tracking-widest">Spend your FWB Tokens here</p>
                    </div>
                </div>

                {loading ? (
                    <div className="flex justify-center py-20">
                        <Loader2 className="w-10 h-10 animate-spin text-amber-500" />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {items.map(item => (
                            <Card key={item.id} className="overflow-hidden border-zinc-200 dark:border-zinc-800">
                                {item.image_url && (
                                    <div className="relative h-48 w-full">
                                        <Image src={item.image_url} alt={item.name} fill className="object-cover" />
                                    </div>
                                )}
                                <CardHeader>
                                    <div className="flex justify-between items-start">
                                        <CardTitle className="text-xl font-bold">{item.name}</CardTitle>
                                        <Badge className="bg-amber-500/10 text-amber-600 border-amber-500/20 flex items-center gap-1">
                                            <Coins className="w-3 h-3 fill-current" />
                                            {item.price_tokens}
                                        </Badge>
                                    </div>
                                    <CardDescription>{item.description}</CardDescription>
                                </CardHeader>
                                <CardFooter className="bg-zinc-50 dark:bg-zinc-900/50 p-4">
                                    <Button 
                                        className="w-full bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 hover:bg-zinc-800 font-black uppercase italic"
                                        disabled={purchasingId === item.id || item.stock_count <= 0}
                                        onClick={() => handlePurchase(item)}
                                    >
                                        {purchasingId === item.id ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <ShoppingBag className="w-4 h-4 mr-2" />}
                                        {item.stock_count <= 0 ? 'Out of Stock' : 'Buy with Tokens'}
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </main>

            <AnimatePresence>
                {redemptionCode && (
                    <motion.div 
                        initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                        className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                    >
                        <Card className="max-w-sm w-full bg-white dark:bg-zinc-900 border-amber-500 shadow-[0_0_50px_rgba(245,158,11,0.2)]">
                            <CardHeader className="text-center">
                                <div className="mx-auto w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mb-4">
                                    <CheckCircle2 className="w-10 h-10 text-green-500" />
                                </div>
                                <CardTitle className="text-2xl font-black uppercase italic italic tracking-tighter">Purchase Confirmed</CardTitle>
                                <CardDescription>Show this code to the merchant to redeem your item.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="bg-zinc-100 dark:bg-zinc-800 p-8 rounded-2xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 text-center">
                                    <span className="text-3xl font-mono font-black tracking-widest text-amber-600">
                                        {redemptionCode}
                                    </span>
                                </div>
                                <div className="flex justify-center">
                                    <QrCode className="w-32 h-32 opacity-20" />
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button className="w-full" onClick={() => setRedemptionCode(null)}>Close</Button>
                            </CardFooter>
                        </Card>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
