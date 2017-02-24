/* Recommender
 Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence 

 This program is free software: you can redistribute it and/or modify 
 it under the terms of the GNU Affero General Public License as 
 published by the Free Software Foundation, either version 3 of the 
 License, or (at your option) any later version. 

 This program is distributed in the hope that it will be useful, 
 but WITHOUT ANY WARRANTY; without even the implied warranty of 
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
 GNU Affero General Public License for more details. 

 You should have received a copy of the GNU Affero General Public License 
 along with this program.  If not, see <http://www.gnu.org/licenses/>. */
package smartcityrecommender;

import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.recommender.IDRescorer;

/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
public class CustomRescorer implements IDRescorer {

    FastIDSet allowedIDs;

    public CustomRescorer(FastIDSet allowedIDs) {
        this.allowedIDs = allowedIDs;
    }

    @Override
    public double rescore(long id, double originalScore) {
        return originalScore;
    }

    @Override
    public boolean isFiltered(long id) {
        return !this.allowedIDs.contains(id);
    }

}
