#!/bin/bash

# Configuration
# Remplacez les valeurs de LOCAL_ par vos identifiants PostgreSQL locaux si différents
LOCAL_DB_HOST="localhost"
LOCAL_DB_PORT="5432"
LOCAL_DB_USER="postgres"
LOCAL_DB_NAME="threadlux"

echo "=== Scripts de Migration des données (Local -> Render) ==="
echo ""
echo "Ce script va exporter votre base de données locale (PostgreSQL) et l'importer dans la base de données de production sur Render."
echo ""
echo "PRÉREQUIS :"
echo "1. Assurez-vous d'avoir 'pg_dump' et 'psql' installés localement."
echo "2. Vous devez avoir créé la base de données PostgreSQL sur Render."
echo "3. Récupérez 'l'External Database URL' depuis le dashboard de votre base de données Render."
echo "   (Exemple: postgres://user:password@hostname.render.com/dbname)"
echo ""

read -p "Entrez votre External Database URL (Render) : " RENDER_DB_URL

if [ -z "$RENDER_DB_URL" ]; then
    echo "Erreur: L'URL de la base de données Render est requise."
    exit 1
fi

echo ""
echo "Étape 1: Export de la base de données locale ($LOCAL_DB_NAME)..."
echo "Note: Vous pourriez être invité à saisir le mot de passe de votre base de données locale."
pg_dump -h $LOCAL_DB_HOST -p $LOCAL_DB_PORT -U $LOCAL_DB_USER -d $LOCAL_DB_NAME -F c -f local_dump.backup

if [ $? -ne 0 ]; then
    echo "Erreur lors de l'exportation de la base de données locale."
    exit 1
fi
echo "Export réussi -> local_dump.backup"

echo ""
echo "Étape 2: Import des données sur Render..."
echo "Avertissement: Cette opération va restaurer les données en écrasant potentiellement les tables existantes."
pg_restore --clean --if-exists --no-acl --no-owner -d "$RENDER_DB_URL" local_dump.backup

if [ $? -ne 0 ]; then
    echo "Erreur lors de l'importation vers Render."
    echo "Il se peut que certaines erreurs soient normales si les tables existaient déjà et que la restauration a réussi."
else
    echo "Import terminé !"
fi

echo ""
echo "Étape 3: Nettoyage"
rm local_dump.backup
echo "Fichier de sauvegarde temporaire supprimé."

echo "=== Migration Terminée ==="
