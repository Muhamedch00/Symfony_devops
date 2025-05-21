pipeline {
    agent any

    environment {
        SONAR_TOKEN = credentials('sonar-token')
        DOCKER_IMAGE = "muhamd/symfony-app"
    }

    stages {
        stage('Cloner le dépôt') {
            steps {
                git url: 'https://github.com/Muhamedch00/Symfony_devops.git'
            }
        }

        stage('Installation des dépendances PHP') {
            steps {
                sh 'docker run --rm -v $PWD:/app -w /app composer:2 composer install --no-interaction --prefer-dist'
            }
        }

        stage('Analyse SonarQube') {
            steps {
                withSonarQubeEnv('SonarQube') {
                    sh 'sonar-scanner'
                }
            }
        }

        stage('Build & Push Docker') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'dockerhub-creds', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                    sh '''
                        docker build -t $DOCKER_IMAGE .
                        echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
                        docker push $DOCKER_IMAGE
                    '''
                }
            }
        }

        stage('Déploiement via Ansible') {
            steps {
                sh 'ansible-playbook -i inventory.ini deploy.yml'
            }
 }
}
}
