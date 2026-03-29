<x-mail::message>
# Votre commande est finalisée !

Bonjour {{ $transaction->acheteur->firstname ?? 'Cher client' }},

Nous vous informons que les fonds pour votre commande **{{ $transaction->commande->reference ?? $transaction->reference }}** ont été débloqués et versés au vendeur.

Cette étape marque la finalisation complète de votre transaction. Nous espérons que votre article vous donne entière satisfaction.

**Détails de la commande :**
- **Référence :** {{ $transaction->commande->reference ?? $transaction->reference }}
- **Montant :** {{ number_format($transaction->amount, 0, ',', ' ') }} {{ $transaction->currency }}

<x-mail::button :url="config('app.frontend_url', 'http://localhost:5173') . '/orders'">
Voir mes commandes
</x-mail::button>

Merci d'avoir choisi {{ config('app.name') }} !  
L'équipe {{ config('app.name') }}
</x-mail::message>
