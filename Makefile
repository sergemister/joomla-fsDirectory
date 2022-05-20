# The base output directory
OUTPUT_DIR=output

# The directory where the package zip content is staged
PACKAGE_OUTPUT_DIR=$(OUTPUT_DIR)/package

# The directory for the individual Joomla extension zip files
PACKAGES_OUTPUT_DIR=$(PACKAGE_OUTPUT_DIR)/packages

PACKAGE_LANG_OUTPUT_DIR=$(PACKAGE_OUTPUT_DIR)/language/en-GB

.PHONY: mod_fsdirectory plg_fsdirectory

$(OUTPUT_DIR)/pkg_fsdirectory.zip: $(PACKAGES_OUTPUT_DIR) $(PACKAGE_LANG_OUTPUT_DIR) pkg_fsDirectory/pkg_fsdirectory.xml pkg_fsDirectory/language/en-GB/pkg_fsdirectory.ini pkg_fsDirectory/language/en-GB/pkg_fsdirectory.sys.ini mod_fsdirectory plg_fsdirectory
	cp pkg_fsDirectory/pkg_fsdirectory.xml $(PACKAGE_OUTPUT_DIR)/
	cp pkg_fsDirectory/language/en-GB/pkg_fsdirectory.ini pkg_fsDirectory/language/en-GB/pkg_fsdirectory.sys.ini $(PACKAGE_LANG_OUTPUT_DIR)/
	(cd $(PACKAGE_OUTPUT_DIR) && zip ../pkg_fsdirectory.zip pkg_fsdirectory.xml language/en-GB/pkg_fsdirectory.ini language/en-GB/pkg_fsdirectory.sys.ini packages/mod_fsdirectory.zip packages/plg_fsdirectory.zip)

mod_fsdirectory:
	make OUTPUT_DIR=../$(PACKAGES_OUTPUT_DIR) -C mod_fsDirectory

plg_fsdirectory:
	make OUTPUT_DIR=../$(PACKAGES_OUTPUT_DIR) -C plg_fsDirectory

$(PACKAGES_OUTPUT_DIR):
	mkdir -p $(PACKAGES_OUTPUT_DIR)

$(PACKAGE_LANG_OUTPUT_DIR):
	mkdir -p $(PACKAGE_LANG_OUTPUT_DIR)
