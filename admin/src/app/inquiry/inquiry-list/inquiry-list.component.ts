import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { AdminService } from 'src/app/_services/admin.service';

@Component({
  selector: 'app-inquiry-list',
  templateUrl: './inquiry-list.component.html',
  styleUrls: ['./inquiry-list.component.css']
})
export class InquiryListComponent implements OnInit {

  constructor(public adminService: AdminService, private toastr: ToastrService,
  ) { }

  todayDate: any;
  user_id: any;

  ngOnInit() {
    this.inquiryList();
    this.inquiryType();
    this.inquiryStatus();

    // const today = new Date();

    // this.todayDate = `${today.getDate().toString().padStart(2, '0')}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getFullYear()}`;
    const today = new Date();
    this.todayDate = `${today.getDate().toString().padStart(2, '0')}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getFullYear()}`;


    this.user_id = this.adminService.getTokenData().user_id;


  }

  formatUpdatedAt(dateStr: string): string {
    const date = new Date(dateStr);
    return `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}`;
  }



  inquiryObj: any = {
    page: 1,
    limit: 10,
    search: '',
    status_id: '',
    total: 0,
    sort_by: '',
    sort_order: '',
    from_date: '',
    to_date: '',
    order_by: '',
    order_type: '',
  }

  inquiry_List: any = [];

  isLoading: any = false;
  inquiryList() {
    this.isLoading = true;
    this.adminService.inquiryList(this.inquiryObj).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_List = response.list;
        this.isLoading = false;
      }
      else {
        this.inquiry_List = [];
        this.isLoading = false;
      }
    })
  }


  onFilter() {
    this.inquiryObj.page = 1;
    this.inquiryList();
  }

  onSearchChange() {
    this.inquiryObj.page = 1;
    this.inquiryList();

    // this.searchSubject.next(this.categoryObj.search); // Trigger search with debounce
  }

  inquiry_type_list: any = [];

  inquiryType() {
    this.adminService.formList({}).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_type_list = response.list;
      }
      else {
        this.inquiry_type_list = [];
      }
    })
  }

  inquiry_status_list: any = [];

  inquiryStatus() {
    this.adminService.inquiryStatusList({}).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_status_list = response.list;
      }
      else {
        this.inquiry_status_list = [];
      }
    })
  }


  isOverlayOpen: boolean = false;
  inquiryDetailObj: any = {}
  toggleDetail(data: any) {
    this.inquiryDetailObj = data;
    // console.log(data);
    // console.log(this.inquiryDetailObj);

    this.isOverlayOpen = !this.isOverlayOpen;

  }

  closeDetail() {
    this.isOverlayOpen = false;
  }




  // follow ups
  public statusObj: any = {
    remarks: "",
    login_user_id: this.adminService.getTokenData().user_id
  };


  //Inquiry Form Submit Funccion
  public isStatusChange: boolean = false;
  confirmChangeStatus(form) {


    console.log(this.statusObj);
    // return;

    // this.isStatusChange = true;
    if (form.valid) {
      this.adminService
        .change_status_inquiry(this.statusObj)
        .subscribe((response: any) => {
          if (response.success == 1) {
            this.statusObj = {
              inquiry_id: "",
              remarks: "",
            };
            // this.isStatusChange = false;
            this.statusOpenF = false;
            this.inquiryList();
            this.toastr.success(response.message);
          } else {
            // this.isStatusChange = false;
            // this.toastr.error(response.message);
          }
        });
    } else {
      // this.isStatusChange = false;

    }
  }



  statusOpenF: boolean = false;

  // change_Status(id: any, type: any) {
  //   if (this.statusObj.id == id) {
  //     this.statusObj.id = "";
  //   } else {
  //     this.statusObj.id = id;
  //     this.statusObj.type = type
  //   }
  // }
  cancelCahnge() {
    this.statusOpenF = false;
  }


  change_Status(data: any) {
    this.statusOpenF = true;
    this.statusObj.inquiry_id = data.id;
    this.statusObj.status_id = data.status_id;

    // console.log(this.statusObj);

  }


  // history

  dummyRecordsHistory = [1, 2, 3];

  historytoggleF: boolean = false;
  inquiryHistory_list: any = [];
  isLoadingHistory: boolean = false;

  inquiryHistoryList(id: any) {
    // this.historytoggleF = !this.historytoggleF;
    this.toggleOverlay();
    this.isLoadingHistory = true;

    this.adminService.inquiryHistoryListing({ inquiry_id: id }).subscribe((response: any) => {
      if (response.success == 1) {
        // console.log(response);
        this.inquiryHistory_list = response.list;
      }
      // else {
      // 	this.inquiryHistory_list = [];
      // }
      setTimeout(() => {
        this.isLoadingHistory = false;
      }, 250);

    }, (error) => {
      this.isLoadingHistory = false;
      console.error('task history api', error)
    })
  }

  toggleOverlay() {
    this.inquiryHistory_list = [];
    this.historytoggleF = !this.historytoggleF;

    if (this.historytoggleF) {
      document.body.classList.add('no-scroll-body');
    } else {
      document.body.classList.remove('no-scroll-body');
    }
  }
}
