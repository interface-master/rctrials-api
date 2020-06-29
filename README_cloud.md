# RCTrials Cloud Deployment

## Step 1 - Create New Cloud Project

- Go to https://console.cloud.google.com/projectcreate
- Give your project a `name` and tap `Create`

## Step 2 - Cloud DB

### Step 2a - Create Cloud DB

- Go to https://console.cloud.google.com/sql/instances
- Tap `Create Instance`
- Select `Choose MySQL`
- Give your instance an `ID`, generate a `root password`, and the rest of the settings can be left as default
- Tap `Create`

### Step 2b - Populate Cloud DB

#### Step 2b.i - Create Storage Bucket

- Go to https://console.cloud.google.com/storage/browser/
- Tap `Create Bucket`
- Give your bucket a `name`, select the same `region` as the Cloud DB Instance, the rest of the settings can be left as default
- Tap `Create`

#### Step 2b.ii - Upload SQL

- Go to https://console.cloud.google.com/storage/browser/
- Select the storage bucket you created in the previous step
- Tap `Upload files` and select the file you wish to upload

#### Step 2b.iii - Import Data

- Go to https://console.cloud.google.com/sql/instances
- Select your Cloud DB Instance
- Tap `Import`, then `Browse` and find the buket and file you created and uploaded in the previous steps
- Tap `Import`

### Step 2c - Manage Users

- Go to https://console.cloud.google.com/sql/instances/
- Select your Cloud DB Instance
- Go to `Users`, tap `Create user account`
- Enter `User Name` and generate a strong random `password`
- Tap `Create`

### Step 2d - Manage Connections

- Go to https://console.cloud.google.com/sql/instances
- Select your Cloud DB Instance
- Go to `Connections`, tap `Add network` next to `Public IP`, and enter the IP of the network from which you wish to grant access (e.g. load balancer, instance, home computer, etc.)
- Tap `Done`, and `Save`


## Step 3 - Application

### Step 3a - Reserve Static Public IP

- Go to https://console.cloud.google.com/networking/addresses
- Tap `Reserve Static Address`
- Enter a `name` for the IP and an optional description, select `IPv4`, `Standard` tier, and the region to match your Cloud DB Instance
- Tap `Reserve`

### Step 3b - Point DNS to IP

- Create a new `Type A` record to point your desired domain/subdomain to the reserved IP from the previous step

### Step 3c - Build Image

- Go to folder with cloned RCTrials repo
```
> cd src
```
- Build image and tag it for the Google Container Registry
(e.g. docker build -t gcr.io/[project_name]/[app_name]:[version] .)
```
> docker build -t gcr.io/randomized-controlled-trials/rctrials-app:latest .
```

### Step 3d - Push to Registry

- Push image to registry
```
docker push gcr.io/randomized-controlled-trials/rctrials-app:latest
```

### Step 3e - Create Instance Group & Template

- Go to https://console.cloud.google.com/compute/instanceGroups
- Tap `Create Instance Group`
- Give your group a `name`, specify the same `region` as the Cloud DB
- Under `Instance template` select `Create a new instance template`
  - Give your template a `name`, for `Machine type` select `f1-micro`, select the checkbox `Deploy a container image` and enter the `Container image` url (e.g. gcr.io/randomized-controlled-trials/rctrials-app:latest), select the checkbox to `Allow HTTP traffic` and `Allow HTTPS traffic`, and the rest of the settings can be left as defaults
  - Tap `Save and continue`
- Under `Autoscaling mode` select `Don't autoscale`, and set `Number of instances` to 1, and the rest of the settings can be left as defaults
- Tap `Create`

### Step 3f - Create Load Balancer

- Go to https://console.cloud.google.com/net-services/loadbalancing/loadBalancers/list
- Tap `Create Load Balancer`
- Under HTTP(S) Load Balancing tap `Start configuration`
- Select `From internet to my VM` and tap `Continue`
- Enter a `name` for this load balancer
- Under `Backend configuration` select `Backend service` and tap `Create a backend service`
  - Enter a `name` for the service, as the type select `instance group` and for the actual instance group select the one created in the previous step, then under `Health check` tap to `Create a new health check`
    - Enter a `name` for the health check (e.g. "basic-http-health-check"), set the `Protocol` to `HTTP`, and the rest of the settings can be left as defaults
    - Tap `Save and continue` to save the health check
  - The rest of the settings can be left as defaults
  - Tap `Create` to create the backend service
- Under `Host and path rules` a catch-all rule should be automatically created to direct all paths to the newly created backend service
- Under `Frontend configuration` enter a name for the frontend service, select the `HTTP` protocol, and under the `IP address` select the one reserved earlier for this load balancer, and tap `Done`
- Review the load balancer configuration and tap `Create`
