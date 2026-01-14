# Disaster Recovery Plan (DRP) for Sibali.id

## Overview
This document outlines the comprehensive Disaster Recovery Plan (DRP) for PT. Siap Belajar Indonesia (Sibali.id), covering scenarios for service outages, data loss, and infrastructure failures. The plan ensures business continuity with defined RTO/RPO targets and step-by-step recovery procedures.

## RTO/RPO Targets per Service
- **Authentication Service**: RTO 15 minutes / RPO 1 hour
- **LMS Platform**: RTO 4 hours / RPO 24 hours
- **CRM System**: RTO 2 hours / RPO 12 hours
- **Payment Gateway**: RTO 30 minutes / RPO 30 minutes
- **Analytics Dashboard**: RTO 8 hours / RPO 48 hours

## Failover Hierarchy
### Service-Level Failover (Active-Standby)
- Primary service failure triggers automatic switch to standby instance
- DNS-based routing with health checks every 30 seconds
- Database read replicas promoted to primary on failure detection

### Region-Level Failover
- Cross-region replication with synchronous writes for critical data
- Load balancer reconfiguration for traffic redirection
- Certificate and secret synchronization across regions

### Multi-Region Failover
- Global traffic management via Geo-DNS
- Data center-level isolation with independent infrastructure
- Manual approval required for full region switch

## Activation Criteria
### Health Score Thresholds
- Service health score < 70%: Warning alerts
- Service health score < 50%: Automatic failover initiation
- Service health score < 20%: Emergency maintenance mode

### Outage Detection Rules
- Response time > 5 seconds for 5 consecutive minutes
- Error rate > 10% for 3 consecutive minutes
- Database connection failures > 50% of pool

## Roles & Responsibilities

### Incident Commander
- Overall decision authority for DR activation
- Coordinates between teams and stakeholders
- Approves escalation to higher severity levels

### SRE Operator
- Executes technical recovery procedures
- Monitors system health during recovery
- Performs validation checks post-recovery

### Database Lead
- Manages database failover and data integrity
- Coordinates backup restoration
- Validates data consistency

### Network Lead
- Handles DNS changes and traffic routing
- Manages load balancer configurations
- Ensures network connectivity

### Communications Lead
- Updates status pages and stakeholders
- Manages customer communications
- Coordinates with external vendors

## Contact Matrix

### Internal Contacts
| Role | Primary | Backup | Phone | Escalation Time |
|------|---------|--------|-------|-----------------|
| Incident Commander | sre-lead@sibali.id | ceo@sibali.id | +62-XXX-XXXX | Immediate |
| SRE Operator | sre-ops@sibali.id | devops@sibali.id | +62-XXX-XXXX | 5 minutes |
| Database Lead | db-admin@sibali.id | sre-db@sibali.id | +62-XXX-XXXX | 10 minutes |

### External Vendor Contacts
| Vendor | Service | Contact | Phone | SLA |
|--------|---------|---------|-------|-----|
| Hostinger | Hosting | support@hostinger.com | +1-XXX-XXXX | 24/7 |
| AWS | Cloud Services | aws-support@sibali.id | +1-XXX-XXXX | Business Hours |

## Communication Plan

### Internal Timeline
- T+0: Incident detection and initial assessment
- T+5min: SRE team notification
- T+15min: Leadership notification for critical incidents
- T+30min: Customer communication if outage >30min

### Status Cadence
- Every 15 minutes during active recovery
- Hourly updates during prolonged incidents
- Daily summary reports post-incident

### Public Status Page Templates
- **Maintenance Notice**: "Scheduled maintenance in progress"
- **Service Degradation**: "Experiencing performance issues"
- **Service Outage**: "Service temporarily unavailable"

## Verification & Acceptance Criteria

### Service Recovery Validation
- Smoke tests: Basic login/logout functionality
- Synthetic transactions: Complete user journey simulation
- SLA checks: Response time <2s, error rate <1%
- Data integrity: Row counts match pre-incident state

### Recovery Completion Checklist
- [ ] All critical services responding
- [ ] Database replication lag <30 seconds
- [ ] User authentication working
- [ ] Payment processing functional
- [ ] Data consistency verified

## Dependencies & Prerequisites

### Access Requirements
- MFA-enabled bastion host access
- KMS decryption permissions for encrypted backups
- Database admin credentials for failover
- DNS management console access

### Infrastructure Prerequisites
- Standby instances pre-provisioned
- Backup encryption keys accessible
- Monitoring systems operational
- Communication channels available

## Recovery Procedures by Severity

### Severity 1: Critical Outage
1. Incident Commander declares DR state
2. Execute emergency-maintenance.sh
3. Run failover-activation.sh
4. Restore from latest backup if needed
5. Validate service recovery
6. Communicate restoration to stakeholders

### Severity 2: Service Degradation
1. Assess degradation scope
2. Scale resources horizontally
3. Optimize database queries
4. Clear application caches
5. Monitor for recovery

### Severity 3: Component Failure
1. Identify failed component
2. Isolate affected systems
3. Replace with standby component
4. Test component functionality
5. Reintegrate into service

## Post-Incident Process

### Post-Mortem Template
- Incident timeline
- Root cause analysis
- Impact assessment
- Lessons learned
- Action items with owners

### Remediation Timeline
- Critical fixes: Within 24 hours
- Process improvements: Within 1 week
- Infrastructure upgrades: Within 1 month

### Policy Updates
- Review RTO/RPO targets annually
- Update contact matrix quarterly
- Test DR procedures bi-annually

## Runbook References
- [Database Restore Script](./emergency-scripts/restore-database.sh)
- [Failover Activation Script](./emergency-scripts/failover-activation.sh)
- [Emergency Maintenance Script](./emergency-scripts/emergency-maintenance.sh)
