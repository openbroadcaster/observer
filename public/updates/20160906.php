<?php

class OBUpdate20160906 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Additional language options for media.';
        return $updates;
    }

    public function run()
    {
        // "Inuktitut" replaces "Inuit" if available.
        $this->db->where('name', 'Inuit');
        $inuit = $this->db->get_one('media_languages');

        if ($inuit) {
            $this->db->where('id', $inuit['id']);
            $this->db->update('media_languages', ['name' => 'Inuktitut']);
        } else {
            $this->db->insert('media_languages', ['name' => 'Inuktitut']);
        }

        // Add additional languages.
        $languages = explode("\n", "Abenaki 
                      Algonquin 
                      Assiniboine 
                      Atikamekw 
                      Babine-Witsuwit'en 
                      Beothuk 
                      Blackfoot 
                      Carrier 
                      Cayuga 
                      Chilcotin 
                      Chipewyan 
                      Comox 
                      Cree
                      Dane-zaa 
                      Delaware
                      Ditidaht 
                      Dogrib 
                      Gitxsan 
                      Gwich'in 
                      Haida 
                      Haisla 
                      Halkomelem
                      Hän 
                      Heiltsuk 
                      Inuinnaqtun
                      Iroquoian
                      Kaska 
                      Klallam 
                      Kutenai 
                      Kwak'wala
                      Lakota 
                      Laurentian 
                      Lillooet 
                      Malecite
                      Mi'kmaq 
                      Mohawk 
                      Munsee 
                      Naskapi 
                      Netsilik 
                      Nicola 
                      Nuu-chah-nulth 
                      Nuxalk 
                      Ojibwa 
                      Ojibwe 
                      Okanagan 
                      Old Montagnais 
                      Oneida 
                      Onondaga 
                      Oowekyala 
                      Ottawa 
                      Plateau Sign 
                      Potawatomi 
                      Saanich 
                      Salish 
                      Sarcee 
                      Sechelt 
                      Sekani 
                      Shuswap 
                      Sinixt 
                      Slavey 
                      Southern Tsimshian 
                      Squamish 
                      Stoney 
                      Susquehannock 
                      Tagish 
                      Tahltan 
                      Thompson 
                      Tlingit 
                      Tsimshian 
                      Tuscarora 
                      Tutchone 
                      Utkuhiksalik
                      Wakashan
                      Wyandot");

        foreach ($languages as $language) {
            $language = trim($language);
            $this->db->insert('media_languages', ['name' => $language]);
        }

        return true;
    }
}
