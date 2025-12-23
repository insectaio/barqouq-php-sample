{
  "family": "${ECS_TASK_FAMILY:-barqouq-php-sample}",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "512",
  "memory": "1024",
  "executionRoleArn": "${ECS_EXECUTION_ROLE_ARN}",
  "taskRoleArn": "${ECS_TASK_ROLE_ARN:-${ECS_EXECUTION_ROLE_ARN}}",
  "containerDefinitions": [
    {
      "name": "app",
      "image": "${IMAGE_URI}",
      "portMappings": [
        {
          "containerPort": 80,
          "hostPort": 80,
          "protocol": "tcp"
        }
      ],
      "essential": true,
      "environment": [
        { "name": "BARQOUQ_GRPC_HOST", "value": "${BARQOUQ_GRPC_HOST}" },
        { "name": "BARQOUQ_GRPC_TLS", "value": "${BARQOUQ_GRPC_TLS:-true}" },
        { "name": "BARQOUQ_SECRET_KEY", "value": "${BARQOUQ_SECRET_KEY}" },
        { "name": "BARQOUQ_SUBDOMAIN", "value": "${BARQOUQ_SUBDOMAIN}" },
        { "name": "APP_ENV", "value": "${APP_ENV:-production}" },
        { "name": "APP_URL", "value": "${APP_URL:-http://localhost}" }
      ],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "${ECS_LOG_GROUP:-/ecs/barqouq-php-sample}",
          "awslogs-region": "${AWS_REGION}",
          "awslogs-stream-prefix": "app"
        }
      }
    }
  ]
}
