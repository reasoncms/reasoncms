# -*- mode: ruby -*-
# vi: set ft=ruby :
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # Every Vagrant virtual environment requires a box to build off of.
  config.vm.box = "ubuntu-raring1304-x63"
  config.vm.box_url = "http://goo.gl/ceHWg"

  # Create a forwarded port mapping to the host machine
  # config.vm.network :forwarded_port, guest: 80, host: 8080

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network :public_network

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  config.vm.network :private_network, ip: "192.168.56.102"

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  config.vm.provider :virtualbox do |virtualbox|
    virtualbox.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    virtualbox.customize ["modifyvm", :id, "--memory", "1024"]
  end

  # If true, then any SSH connections made will enable agent forwarding.
  # Default value: false
  # config.ssh.forward_agent = true

  # Share a additional directories to the guest VM.
  # @todo remove luther_css_js when it's in the reason_package
  # @todo find a better way to get mysql loaded
  config.vm.synced_folder ".", "/var/reason_package"
  config.vm.synced_folder "./reason_4.0/data", "/var/reason_package/reason_4.0/data", :owner=>"www-data", :group=>"www-data"
  config.vm.synced_folder "../luther_css_js/javascripts", "/var/www/javascripts/", :owner=>"www-data", :group=>"www-data"
  config.vm.synced_folder "../luther_css_js/stylesheets", "/var/www/stylesheets/", :owner=>"www-data", :group=>"www-data"
  config.vm.synced_folder "../luther_css_js/images", "/var/www/images/", :owner=>"www-data", :group=>"www-data"

  config.vm.synced_folder "../sql", "/var/sql"

  # Ansible provisioning
  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "provisioning/playbook.yml"
  end
end
