Installing in docker
####################

.. contents::

Method n°1: Docker Compose
<<<<<<<<<<<<<<<<<<<<<<<<<<

Build and start:

``docker-compose up -d``

Method n°2: Docker alone
<<<<<<<<<<<<<<<<<<<<<<<<

Step 0: Create a volume
=======================

In order to have persistent data, we can create a volume to store all the data
there.

``docker volume create phpreport``

This will create a volume called `phpreport`.

Step 1: Create the image
===============================

Now we can create the image that contains phpreport.

``docker build . -t phpreport``

This will create an image called `phpreport`

Step 2: Starting a container with phpreport
===========================================

Now we can start a phpreport in a container, using the previously created volume
to have persistent data.

``docker run -d -v phpreport:/var/lib/postgresql -p 80:80 phpreport``

Post install
<<<<<<<<<<<<

After a while, open http://localhost/phpreport with the browser.

The default user is `admin` and default password is `admin`.
