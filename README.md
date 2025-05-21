# 📘 Symfony CRM – Projet DevOps 2025

---

## 📝 Présentation

Ce projet est une **application CRM** (Customer Relationship Management) construite avec **Symfony 7**. Il permet à un utilisateur authentifié de gérer ses **clients** et leurs **factures** dans une interface sécurisée.  
L'approche DevOps intègre une chaîne CI/CD complète avec **Jenkins**, **Docker**, **SonarQube**, **DockerHub** et **Ansible**.

---

## 🧰 Technologies Utilisées

| Catégorie       | Outils / Technologies                  |
|-----------------|----------------------------------------|
| Backend         | PHP 8.2, Symfony 7                     |
| Frontend        | Twig, Bootstrap 5                      |
| Base de données | MySQL                                  |
| Tests           | PHPUnit, Couverture `coverage.xml`    |
| Conteneurisation| Docker, Docker Compose                 |
| CI/CD           | Jenkins, SonarQube, DockerHub          |
| Déploiement     | Ansible (via `deploy.yml`)             |

---

## 🛠️ Fonctionnalités Principales

### 🔐 Authentification
- Login via interface Symfony
- Pas de système d'inscription
- Sécurité via Symfony Security Bundle

### 👥 Gestion des Clients
- CRUD complet (Créer, Lire, Mettre à jour, Supprimer)
- Champs : Nom du gérant, Entreprise, Téléphone, Adresse, Ville, Pays

### 📄 Gestion des Factures
- CRUD complet des factures par utilisateur
- Champs : Numéro unique, Date, Montant, État (payée, partielle, impayée), Note

---

## 🚀 Mise en Place du Projet

### 1. Cloner le dépôt
```bash
git clone https://github.com/Muhamedch00/Symfony_devops.git
cd Symfony_devops
```

### 2. Lancer l’environnement Docker
```bash
docker-compose up -d --build
```

---

## 🔁 Intégration Continue – Jenkins

### Pipeline CI/CD
1. **Clonage** du projet depuis GitHub
2. **Installation** des dépendances avec Composer
3. **Tests** avec PHPUnit et génération de `coverage.xml`
4. **Analyse SonarQube** avec couverture
5. **Build Docker** image : `muhamd/symfony-app:${BUILD_NUMBER}`
6. **Push DockerHub**
7. **Déploiement Ansible** (si qualité validée)

---

## 🧪 Tests et Couverture

### Exécution manuelle :
```bash
php bin/phpunit --coverage-clover=coverage.xml
```

### Tests intégrés dans Jenkins :
- Résultats affichés dans les logs de pipeline
- Couverture lue par SonarQube

---

## 📊 Analyse SonarQube

### Lancer l’analyse :
```bash
docker run --rm -v "$PWD":/usr/src sonarsource/sonar-scanner-cli   -Dsonar.projectKey=SymfonyDevOps   -Dsonar.sources=.   -Dsonar.exclusions=vendor/,var/,tests/**   -Dsonar.php.coverage.reportPaths=coverage.xml   -Dsonar.host.url=http://sonarqube:9000   -Dsonar.login=<SONAR_TOKEN>
```

---

## 📦 Déploiement via Ansible

### Fichiers requis :
- `inventory.ini`
- `deploy.yml`

### Lancer le déploiement :
```bash
ansible-playbook -i inventory.ini deploy.yml
```

---

## 📁 Structure du Projet

```
├── config/
├── docker/
├── public/
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   └── Security/
├── templates/
├── tests/
├── Dockerfile
├── docker-compose.yml
├── Jenkinsfile
├── deploy.yml
├── inventory.ini
└── README.md
```

---



## 👨‍💻 Réalisé par

  Chourak Mohamed , Amrani Ayoub , Mrhar Ouissal , Benzrga Soufiane , El Gairi Tasnime 
---

## 📌 Statut

✔️ Application opérationnelle  
✔️ CI/CD fonctionnel  
✔️ Analyse de qualité intégrée  
✔️ Image poussée sur DockerHub  
✔️ Déploiement automatisé via Ansible

---
