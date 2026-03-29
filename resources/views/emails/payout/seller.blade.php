<x-mail::message>
# Vos fonds ont été libérés !

Bonjour {{ $transaction->vendeur->firstname }},

Nous avons le plaisir de vous informer que les fonds pour votre vente **{{ $transaction->reference }}** ont été débloqués et transférés vers votre compte.

**Détails du transfert :**
- **Référence :** {{ $transaction->reference }}
- **Montant :** {{ number_format($transaction->amount, 0, ',', ' ') }} {{ $transaction->currency }}
- **Date :** {{ $transaction->escrow_released_at->format('d/m/Y à H:i') }}

Le virement a été initié via FedaPay et devrait apparaître sur votre compte sous peu.

<x-mail::button :url="config('app.frontend_url', 'http://localhost:5173') . '/dashboard/payouts'">
Voir mes paiements
</x-mail::button>

Merci de votre confiance,  
L'équipe {{ config('app.name') }}
</x-mail::message>
